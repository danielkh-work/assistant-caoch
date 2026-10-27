# Starter Global Plays Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let the admin mark a subset of Global Plays as "starter" plays. Every league created from now on starts out seeing only the starter set; every league that already exists is unaffected.

**Architecture:** Add `is_starter_play` (boolean, default `true`) to `plays`. Seed `league_play_overrides` rows with `status = hidden` for every non-starter Global Play at the moment a new league is created, reusing the hide/restore mechanism the Global Plays feature already shipped. No change to the plays-visibility query itself.

**Tech Stack:** Laravel 10 (backend repo `assistant-caoch`, branch `feature/starter-global-plays`, already created off current `v3`), Vue 3 (frontend repo `coach-vue`, branch `feature/starter-global-plays`, already created off current `origin/v3`).

**Repos:** Tasks 1-4 are in `C:\laragon\www\assistant-caoch` (already on branch `feature/starter-global-plays`). Task 5 is in `F:\coach-vue` (already on its own branch `feature/starter-global-plays`).

**Full spec:** `docs/superpowers/specs/2026-10-25-starter-global-plays-design.md` (in the backend repo).

---

### Task 1: `is_starter_play` column

**Files:**
- Create: `database/migrations/2026_10_25_090000_add_is_starter_play_to_plays_table.php`
- Modify: `app/Models/Play.php:22-28` (`$fillable`)

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Default true: every existing Global Play stays visible to brand-new
        // leagues exactly as it is today until an admin opts specific plays out.
        Schema::table('plays', function (Blueprint $table) {
            $table->boolean('is_starter_play')->default(true)->after('is_global');
        });
    }

    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->dropColumn('is_starter_play');
        });
    }
};
```

- [ ] **Step 2: Run it against the dev DB**

Run: `php artisan migrate --path=database/migrations/2026_10_25_090000_add_is_starter_play_to_plays_table.php`
Expected: `Migrating:` then `Migrated:` for this one file, no errors.

- [ ] **Step 3: Apply the same column to the testing DB directly**

The testing DB (`assistant_coach_testing`, port 3307) is ~34 migrations behind and is patched at the DB level rather than run through the full backlog — this is the established pattern for this feature (see the original Global Plays migrations). Run:

```
mysql -h 127.0.0.1 -P 3307 -u root assistant_coach_testing -e "ALTER TABLE plays ADD COLUMN is_starter_play TINYINT(1) NOT NULL DEFAULT 1 AFTER is_global;"
```

Expected: no output, no error. Verify with:

```
mysql -h 127.0.0.1 -P 3307 -u root assistant_coach_testing -e "SHOW COLUMNS FROM plays LIKE 'is_starter_play';"
```
Expected: one row showing the new column, `Default = 1`.

- [ ] **Step 4: Add the column to `Play::$fillable`**

In `app/Models/Play.php`, change:

```php
    protected $fillable = [
        'league_id', 'is_global', 'created_by', 'created_by_user_id',
        'play_name', 'play_type', 'zone_selection',
        'min_expected_yard', 'max_expected_yard',
        'pre_snap_motion', 'play_action_fake',
        'possession', 'video_path',
    ];
```

to:

```php
    protected $fillable = [
        'league_id', 'is_global', 'is_starter_play', 'created_by', 'created_by_user_id',
        'play_name', 'play_type', 'zone_selection',
        'min_expected_yard', 'max_expected_yard',
        'pre_snap_motion', 'play_action_fake',
        'possession', 'video_path',
    ];
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_10_25_090000_add_is_starter_play_to_plays_table.php app/Models/Play.php
git commit -m "add is_starter_play column to plays, default true"
```

---

### Task 2: Seed hidden overrides for non-starter Global Plays at league creation

**Files:**
- Modify: `app/Http/Controllers/Api/SportController.php:1-18` (imports), `:47-72` (`store()`)
- Test: `tests/Feature/GlobalPlaysTest.php` (append new tests before the final closing `}`)

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/GlobalPlaysTest.php`, just before the class's final closing `}` (after `deleting_a_customized_clone_directly_restores_access_to_the_original_global_play`):

```php

    /** @test */
    public function creating_a_new_league_seeds_hidden_overrides_for_non_starter_global_plays()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        // givePlayPermissions() uses syncPermissions(), which REPLACES the whole set -
        // it must run before any other givePermissionTo() call, or it wipes it out.
        $this->givePlayPermissions($hc);
        $hc->givePermissionTo('league.create');

        $starterGlobal = $this->makeGlobalPlay('Starter Play One'); // column default: is_starter_play = true
        $nonStarterGlobal = $this->makeGlobalPlay('Non Starter Play One');
        $nonStarterGlobal->is_starter_play = false;
        $nonStarterGlobal->save();

        $sportId = DB::table('sports')->insertGetId(['title' => 'Starter Set Test Sport']);
        $leagueRuleId = DB::table('league_rules')->value('id') ?? 1;

        Sanctum::actingAs($hc);
        $response = $this->postJson('/api/leaque-create', [
            'sport_id' => $sportId,
            'location' => 'Test City',
            'league_rule_id' => $leagueRuleId,
            'number_of_team' => 1,
            'title' => 'Starter Set League',
            'team_name' => [
                ['name' => 'My Team', 'is_practice' => 0],
            ],
        ]);

        $response->assertStatus(200);
        $leagueId = $response->json('data.id');

        $this->assertDatabaseHas('league_play_overrides', [
            'league_id' => $leagueId,
            'global_play_id' => $nonStarterGlobal->id,
            'status' => 'hidden',
        ]);
        $this->assertDatabaseMissing('league_play_overrides', [
            'league_id' => $leagueId,
            'global_play_id' => $starterGlobal->id,
        ]);

        $listResponse = $this->getJson('/api/upload-play-list?league_id=' . $leagueId);
        $names = collect($listResponse->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Starter Play One'));
        $this->assertFalse($names->contains('Non Starter Play One'));
    }

    /** @test */
    public function creating_a_new_league_seeds_no_overrides_when_every_global_play_is_a_starter_play()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $hc->givePermissionTo('league.create');
        $this->makeGlobalPlay('Already A Starter'); // column default: is_starter_play = true

        $sportId = DB::table('sports')->insertGetId(['title' => 'No Seed Test Sport']);
        $leagueRuleId = DB::table('league_rules')->value('id') ?? 1;

        Sanctum::actingAs($hc);
        $response = $this->postJson('/api/leaque-create', [
            'sport_id' => $sportId,
            'location' => 'Test City',
            'league_rule_id' => $leagueRuleId,
            'number_of_team' => 1,
            'title' => 'No Restriction League',
            'team_name' => [
                ['name' => 'My Team', 'is_practice' => 0],
            ],
        ]);

        $response->assertStatus(200);
        $leagueId = $response->json('data.id');
        $this->assertSame(0, \App\Models\LeaguePlayOverride::where('league_id', $leagueId)->count());
    }

    /** @test */
    public function changing_starter_status_after_a_league_already_exists_does_not_retroactively_hide_anything_for_it()
    {
        $hc = User::factory()->create(['role' => 'head_coach', 'status' => 'approved']);
        $this->givePlayPermissions($hc);
        $league = $this->makeLeague($hc); // created directly, as if it predates this feature
        $global = $this->makeGlobalPlay('Pre-Existing League Play'); // starts as starter by default

        $global->is_starter_play = false; // admin un-stars it AFTER the league already exists
        $global->save();

        Sanctum::actingAs($hc);
        $response = $this->getJson('/api/upload-play-list?league_id=' . $league->id);
        $names = collect($response->json('data'))->pluck('play_name');
        $this->assertTrue($names->contains('Pre-Existing League Play'));
    }
```

- [ ] **Step 2: Run the new tests to verify they fail**

Run: `php artisan test --filter=creating_a_new_league_seeds_hidden_overrides_for_non_starter_global_plays`
Expected: FAIL — `league_play_overrides` has no row for the new league (nothing seeds it yet).

Run: `php artisan test --filter=changing_starter_status_after_a_league_already_exists`
Expected: PASS already (nothing hooks into league creation yet, so this one can't fail — that's fine, it's a regression guard for after Step 3, not a TDD-red test. Confirm it passes now and keep it passing after Step 3.)

- [ ] **Step 3: Implement the seeding**

In `app/Http/Controllers/Api/SportController.php`, add the import:

```php
use App\Models\Play;
```

alongside the existing `use App\Models\League;` etc. at the top of the file.

Then in `store()`, change:

```php
           $League->flag_tbd =$request->flag_tbd;
        //    $League->warning_time_minutes =$request->warning_time_minutes;
           $League->save();
           $practiceTeams = [];
```

to:

```php
           $League->flag_tbd =$request->flag_tbd;
        //    $League->warning_time_minutes =$request->warning_time_minutes;
           $League->save();

           $nonStarterGlobalPlayIds = Play::where('is_global', true)
               ->where('is_starter_play', false)
               ->pluck('id');

           if ($nonStarterGlobalPlayIds->isNotEmpty()) {
               $now = now();
               $overrideRows = $nonStarterGlobalPlayIds->map(fn ($playId) => [
                   'league_id' => $League->id,
                   'global_play_id' => $playId,
                   'status' => 'hidden',
                   'customized_play_id' => null,
                   'created_by_user_id' => null,
                   'created_at' => $now,
                   'updated_at' => $now,
               ])->all();

               DB::table('league_play_overrides')->insert($overrideRows);
           }

           $practiceTeams = [];
```

This stays inside the method's existing `try { ... } catch (\Throwable $th) { DB::rollBack(); ... }` block, so a failure here rolls back the whole league creation exactly like any other failure in this method already does.

- [ ] **Step 4: Run the tests again to verify they pass**

Run: `php artisan test --filter=GlobalPlaysTest`
Expected: PASS, all tests in the file (including the 3 new ones and every pre-existing one — 21 existing + 3 new = 24).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/SportController.php tests/Feature/GlobalPlaysTest.php
git commit -m "seed hidden overrides for non-starter global plays when a league is created"
```

---

### Task 3: Admin endpoint to toggle a Global Play's starter status

**Files:**
- Modify: `app/Http/Controllers/PlayController.php` (add `toggleStarter` method)
- Modify: `routes/web.php:70-78` (the `PlayController::class` group)
- Create: `tests/Feature/PlayControllerAdminStarterTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/PlayControllerAdminStarterTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\Play;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlayControllerAdminStarterTest extends TestCase
{
    use DatabaseTransactions;

    private function makeGlobalPlay(bool $isStarter = true): Play
    {
        return Play::create([
            'play_name' => 'Admin Starter Test Play',
            'play_type' => 1,
            'zone_selection' => 1,
            'min_expected_yard' => '0',
            'max_expected_yard' => '0',
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'possession' => 'offensive',
            'video_path' => '',
            'league_id' => null,
            'is_global' => true,
            'is_starter_play' => $isStarter,
        ]);
    }

    private function makeLeagueScopedPlay(): Play
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $sportId = DB::table('sports')->insertGetId(['title' => 'Admin Starter Test Sport']);
        $league = new League();
        $league->user_id = $admin->id;
        $league->sport_id = $sportId;
        $league->league_rule_id = DB::table('league_rules')->value('id') ?? 1;
        $league->title = 'Admin Starter Test League';
        $league->number_of_team = 1;
        $league->save();

        return Play::create([
            'play_name' => 'League Scoped Test Play',
            'play_type' => 1,
            'zone_selection' => 1,
            'min_expected_yard' => '0',
            'max_expected_yard' => '0',
            'pre_snap_motion' => 1,
            'play_action_fake' => 1,
            'possession' => 'offensive',
            'video_path' => '',
            'league_id' => $league->id,
            'is_global' => false,
        ]);
    }

    /** @test */
    public function admin_can_toggle_a_global_plays_starter_status()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makeGlobalPlay(isStarter: true);

        $response = $this->actingAs($admin)->postJson(route('play.toggle-starter', $play->id));

        $response->assertStatus(200);
        $response->assertJson(['is_starter_play' => false]);
        $this->assertFalse($play->fresh()->is_starter_play);
    }

    /** @test */
    public function toggling_starter_status_twice_returns_it_to_the_original_value()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makeGlobalPlay(isStarter: true);

        $this->actingAs($admin)->postJson(route('play.toggle-starter', $play->id));
        $this->actingAs($admin)->postJson(route('play.toggle-starter', $play->id));

        $this->assertTrue($play->fresh()->is_starter_play);
    }

    /** @test */
    public function a_league_scoped_play_cannot_be_made_a_starter_play()
    {
        $admin = User::factory()->create(['status' => 'approved']);
        $play = $this->makeLeagueScopedPlay();

        $response = $this->actingAs($admin)->postJson(route('play.toggle-starter', $play->id));

        $response->assertStatus(422);
        $this->assertTrue($play->fresh()->is_starter_play); // column default, never changed
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=PlayControllerAdminStarterTest`
Expected: FAIL — `route('play.toggle-starter', ...)` doesn't exist yet (`RouteNotFoundException`).

- [ ] **Step 3: Add the route**

In `routes/web.php`, change:

```php
    Route::controller(PlayController::class)->group(function () {
        Route::get('play', 'index')->name('play.index');
        Route::get('play/create', 'create')->name('play.create'); // <- Move this above
        Route::get('play/{id}', 'show')->name('play.show');
        Route::post('play', 'store')->name('play.store');

        Route::get('play/{id}/edit', 'edit')->name('play.edit');
        Route::put('play/{id}', 'update')->name('play.update');
        Route::delete('play/{id}', 'destroy')->name('play.destroy');
    });
```

to:

```php
    Route::controller(PlayController::class)->group(function () {
        Route::get('play', 'index')->name('play.index');
        Route::get('play/create', 'create')->name('play.create'); // <- Move this above
        Route::get('play/{id}', 'show')->name('play.show');
        Route::post('play', 'store')->name('play.store');

        Route::get('play/{id}/edit', 'edit')->name('play.edit');
        Route::put('play/{id}', 'update')->name('play.update');
        Route::delete('play/{id}', 'destroy')->name('play.destroy');
        Route::post('play/{id}/toggle-starter', 'toggleStarter')->name('play.toggle-starter');
    });
```

- [ ] **Step 4: Add the controller method**

In `app/Http/Controllers/PlayController.php`, add this method (e.g. right after `destroy()`, before `store()`):

```php
    public function toggleStarter($id)
    {
        $play = Play::findOrFail($id);

        if (!$play->is_global) {
            return response()->json(['message' => 'Only Global Plays can be starter plays.'], 422);
        }

        $play->is_starter_play = !$play->is_starter_play;
        $play->save();

        return response()->json(['is_starter_play' => $play->is_starter_play]);
    }
```

- [ ] **Step 5: Run the tests again to verify they pass**

Run: `php artisan test --filter=PlayControllerAdminStarterTest`
Expected: PASS, all 3 tests.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PlayController.php routes/web.php tests/Feature/PlayControllerAdminStarterTest.php
git commit -m "add admin endpoint to toggle a global play's starter status"
```

---

### Task 4: Admin UI — Starter checkbox column in the Plays list

**Files:**
- Modify: `app/Http/Controllers/PlayController.php:24-63` (`index()`)
- Modify: `resources/views/plays/index.blade.php`

No automated test for this task — it's a DataTables column rendered server-side plus a jQuery AJAX call, and the existing admin Plays screen has no test coverage to extend (confirmed: no test file exercises `play.index`/`play.store`/etc. today). Verify manually per Step 4 below.

- [ ] **Step 1: Add the `starter` column to the DataTables response**

In `app/Http/Controllers/PlayController.php`, change:

```php
                    ->addColumn('action', function($row){
                    $editUrl = route('play.edit', ['id' => $row->id]);
                    $deleteUrl = route('play.destroy', ['id' => $row->id]);

                   

                    return '
                        <a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1">Edit</a>
                       
                    ';
                    })
                    //  <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                    //         ' . csrf_field() . '
                    //         ' . method_field('DELETE') . '
                    //         <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                    //     </form>
                ->rawColumns(['action'])
                ->make(true);
```

to:

```php
                    ->addColumn('action', function($row){
                    $editUrl = route('play.edit', ['id' => $row->id]);
                    $deleteUrl = route('play.destroy', ['id' => $row->id]);

                   

                    return '
                        <a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1">Edit</a>
                       
                    ';
                    })
                    //  <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                    //         ' . csrf_field() . '
                    //         ' . method_field('DELETE') . '
                    //         <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                    //     </form>
                    ->addColumn('starter', function($row){
                        if (!$row->is_global) {
                            return '<span class="text-muted">&mdash;</span>';
                        }
                        $checked = $row->is_starter_play ? 'checked' : '';
                        return '<input type="checkbox" class="starter-toggle" data-id="' . $row->id . '" ' . $checked . '>';
                    })
                ->rawColumns(['action', 'starter'])
                ->make(true);
```

- [ ] **Step 2: Add the column to the table header and the DataTables JS config**

In `resources/views/plays/index.blade.php`, change the header:

```html
                        <tr>
                            <th>No</th>
                            <th>Play Name</th>
                            <th>Roles</th>
                            <th>Min Expected Yard</th>
                            <th>preferred_down</th>
                            <th>possession</th>
                            <th>description</th>
                            <th>strategies</th>
                            <th width="100px">Action</th>
                        </tr>
```

to:

```html
                        <tr>
                            <th>No</th>
                            <th>Play Name</th>
                            <th>Roles</th>
                            <th>Min Expected Yard</th>
                            <th>preferred_down</th>
                            <th>possession</th>
                            <th>description</th>
                            <th>strategies</th>
                            <th width="80px">Starter</th>
                            <th width="100px">Action</th>
                        </tr>
```

and the `columns:` array:

```js
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'play_name', name: 'play_name'},
                    {data: 'roles', name: 'roles'},
                    {data: 'min_expected_yard', name: 'min_expected_yard'},
                    {data: 'preferred_down', name: 'preferred_down'},
                    {data: 'possession', name: 'possession'},
                    {data: 'description', name: 'description'},
                    {data: 'strategies', name: 'strategies'},
                
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
```

to:

```js
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'play_name', name: 'play_name'},
                    {data: 'roles', name: 'roles'},
                    {data: 'min_expected_yard', name: 'min_expected_yard'},
                    {data: 'preferred_down', name: 'preferred_down'},
                    {data: 'possession', name: 'possession'},
                    {data: 'description', name: 'description'},
                    {data: 'strategies', name: 'strategies'},
                    {data: 'starter', name: 'starter', orderable: false, searchable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
```

- [ ] **Step 3: Wire up the checkbox's AJAX call**

In the same file's `@section('script')` block, right after the `$('#roleFilter').on('change', ...)` handler and before the closing `});` of the `$(function () { ... })`, add:

```js
            $(document).on('change', '.starter-toggle', function () {
                const checkbox = $(this);
                const id = checkbox.data('id');
                $.ajax({
                    url: '/play/' + id + '/toggle-starter',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    error: function (xhr) {
                        checkbox.prop('checked', !checkbox.prop('checked')); // revert on failure
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not update starter status.');
                    }
                });
            });
```

- [ ] **Step 4: Manually verify**

Run: `php artisan serve` (if not already running), log into the admin panel, open `/play`.
Expected: a "Starter" column appears; Global Plays show a checked checkbox (column default `true`), league-scoped plays show an em-dash; unchecking a Global Play's box and reloading the page keeps it unchecked (confirms the AJAX call persisted); checking it again on a league-scoped play's row is impossible (no checkbox rendered there at all).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PlayController.php resources/views/plays/index.blade.php
git commit -m "add Starter checkbox column to the admin Plays list"
```

---

### Task 5: Restore the Hidden Global Plays panel (frontend)

**Repo:** `F:\coach-vue`, branch `feature/starter-global-plays` (already created off current `origin/v3`).

**Files:**
- Modify: `src/views/PreSeasonMode/UploadPlay/UploadedPlay.vue:114-124`

- [ ] **Step 1: Uncomment the panel**

Change:

```html
    <!-- Hidden Global Plays panel hidden per request; logic kept below for later use.
    <div v-if="hiddenGlobalPlays.length && userRole === 'head_coach'" class="up-hidden-panel">
      <h3>Hidden Global Plays</h3>
      <ul>
        <li v-for="hp in hiddenGlobalPlays" :key="hp.id">
          {{ hp.play_name }}
          <button @click="restoreHiddenPlay(hp.id)">Restore</button>
        </li>
      </ul>
    </div>
    -->
```

to:

```html
    <!-- Hidden Global Plays (head coach only) -->
    <div v-if="hiddenGlobalPlays.length && userRole === 'head_coach'" class="up-hidden-panel">
      <h3>Hidden Global Plays</h3>
      <ul>
        <li v-for="hp in hiddenGlobalPlays" :key="hp.id">
          {{ hp.play_name }}
          <button @click="restoreHiddenPlay(hp.id)">Restore</button>
        </li>
      </ul>
    </div>
```

This is this feature's reason to exist: without it, a head coach on a brand-new league has no way back to a non-starter Global Play they want after all.

- [ ] **Step 2: Run the frontend test suite**

Run: `npx vitest run`
Expected: PASS (this is a template-only change; no existing test targets this panel specifically, so the suite should show the same pass count as before the change).

- [ ] **Step 3: Run the build**

Run: `npm run build`
Expected: builds successfully, no new errors.

- [ ] **Step 4: Commit**

```bash
git add src/views/PreSeasonMode/UploadPlay/UploadedPlay.vue
git commit -m "restore the Hidden Global Plays panel now that new leagues can seed non-starter overrides"
```

---

### After all tasks

Run the full backend suite once more (`php artisan test`) and confirm no new failures beyond the project's known pre-existing baseline, then hand off to `superpowers:finishing-a-development-branch` for both branches (push + PR, same flow already used for the original Global Plays feature).
