# Starter Global Plays (Admin-Curated Onboarding Set) — Design

> **For agentic workers:** This spec feeds `superpowers:writing-plans` next. Do not implement from this document directly — wait for the generated plan.

**Goal:** Let the admin hand-pick a subset of the existing Global Plays catalog (currently 280 plays) as the "starter set." Every league created from now on starts out seeing only that starter set instead of the full Global Plays catalog, while every league that already exists keeps seeing everything it sees today.

**Architecture:** Reuse the existing `league_play_overrides` hide/restore mechanism built for the Global Plays feature, rather than adding a second visibility system. At league-creation time, seed a `status = hidden` override row for every Global Play that isn't in the admin's starter set. The existing `index()` visibility query, the existing `restore()` endpoint, and the existing `hiddenGlobalPlays()` endpoint all keep working unchanged — a league simply starts with more rows already hidden than it used to.

**Tech Stack:** Laravel 10 (backend, both the API and the Blade admin panel), Vue 3 (frontend, re-enabling UI already built).

---

## 1. Data model change

**Migration (new):** add `is_starter_play` (boolean, default `true`, not null) to the `plays` table.

- Only meaningful when `is_global = true`. League-scoped plays ignore this column entirely.
- Default `true` on the column itself means: if this migration ran and nobody has touched the admin UI yet, every existing Global Play is still in the starter set, and newly-created leagues keep seeing the full catalog exactly as they do today. The admin opts out of plays one at a time rather than opting in from zero — ships safely, no onboarding regression on day one.

No change to `league_play_overrides` or any other table.

## 2. Admin UI: curating the starter set

**Where:** the existing admin "Plays" screen (`resources/views/plays/index.blade.php`), which is a server-side Yajra DataTable backed by `PlayController::index()` (the Blade admin controller at `app/Http/Controllers/PlayController.php`, not the API one).

**Change:**
- Add a "Starter" column to the DataTable. For rows where `is_global` is true, render a checkbox reflecting `is_starter_play`. For league-scoped rows, render a plain dash (not applicable — those plays were never part of the Global catalog new leagues are seeded from).
- Checkbox `onchange` fires an AJAX request to a new route, e.g. `POST /play/{id}/toggle-starter`, handled by a new `PlayController::toggleStarter($id)` method:
  - 404 if the play doesn't exist.
  - 422 if `is_global` is false (a league-scoped play can never be a starter play).
  - Otherwise flips `is_starter_play` and returns the new value as JSON.
- No new page, no bulk-select UI — the existing DataTable's search/filter already lets the admin find the play they want, and toggling one checkbox at a time is enough for this use case (YAGNI: a bulk picker can be added later if curating 280 rows one at a time turns out to be painful in practice, but nothing here blocks adding one).

## 3. League creation: seeding the starter set

**Where:** `App\Http\Controllers\Api\SportController::store()` — this is the actual endpoint the frontend's "Create League" screen calls (`POST /leaque-create`), despite living on `SportController` rather than a `LeagueController`.

**Change:** immediately after `$League->save()` succeeds (still inside the existing `DB::beginTransaction()` block, so this is atomic with league creation — if seeding fails, the whole league creation rolls back same as any other failure in that method today):

```php
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
```

- `created_by_user_id` is `null` here deliberately — this hide was triggered by system onboarding logic, not a head coach action, which keeps it distinguishable from a manual hide if that ever matters later.
- This is a single bulk insert, not a loop of `Eloquent::create()` calls — cheap even if the catalog grows well past 280 plays.
- No other part of league creation changes. Team creation, the practice-game bootstrap, and the response shape are untouched.

**What this does *not* do:** it does not touch any existing league's `league_play_overrides` rows. A league created before this ships keeps whatever overrides it already has (likely none), so it keeps seeing the full Global Plays catalog exactly as today.

**What happens to a Global Play added *after* a league already exists:** no override row is seeded for it retroactively, so it shows up immediately for every league regardless of starter status — onboarding defaults only apply at the moment a league is created, not as an ongoing filter. This matches how the admin already expects "new Global Play = visible to everyone" to work.

## 4. Frontend: restoring the Hidden Global Plays panel

**Where:** `src/views/PreSeasonMode/UploadPlay/UploadedPlay.vue` — the "Hidden Global Plays" panel was commented out earlier today (template block + its logic were left in place, just not rendered).

**Change:** uncomment the panel. No other frontend change is needed — `fetchHiddenGlobalPlays` / `restoreGlobalPlay` in `src/composables/usePlay.js` and the `/hidden-global-plays` + `/restore-global-play/{id}` endpoints already exist and already work against the `league_play_overrides` table regardless of whether a hide came from a head coach's own action or from this new onboarding seed. A head coach on a brand-new league who wants a non-starter Global Play back clicks "Restore" exactly the way a head coach who manually hid a play already does today.

This is why Section 3 matters: without restoring this panel, a new league's missing Global Plays would be invisible with no way back.

## 5. Testing

- **Backend unit/feature tests** (extending `tests/Feature/GlobalPlaysTest.php` and friends):
  - Creating a league with some Global Plays marked `is_starter_play = false` results in exactly those plays being hidden for the new league (query the plays-list endpoint for that league, assert the non-starter ones are absent and the starter ones are present).
  - Creating a league when *all* Global Plays are starter plays results in no seeded overrides (no behavior change from today).
  - An existing league (created before the seed logic ran) is unaffected by changing `is_starter_play` afterwards — no new overrides appear for it after the fact.
  - A head coach can restore a seeded-hidden Global Play on their new league via the existing `restore-global-play` endpoint, and it then appears in that league's plays list.
  - `PlayController::toggleStarter` rejects toggling a non-global play (422), and flips `is_starter_play` correctly for a global one.
- **Frontend:** no new unit tests needed beyond confirming the existing `GlobalPlaysTest`-equivalent frontend coverage still passes with the panel visible again; this is a straight revert of today's comment-out, already covered by existing tests for `fetchHiddenGlobalPlays`/`restoreGlobalPlay` if any exist, otherwise a quick manual check (panel renders, Restore button works) is enough.

## 6. Out of scope (explicitly)

- No change to visibility for any league that exists before this ships.
- No bulk admin picker UI — single-checkbox toggle only, per Section 2.
- No new permission — the admin Blade panel (where this control lives) is already gated by the `auth` middleware/session login the same way `play.index`/`play.create`/`play.edit`/`play.destroy` are, with no Spatie permission check layered on top of it. The new `toggle-starter` route follows that same existing pattern; this is an admin-panel-only control, not exposed to head coaches or assistant coaches at all.
- No retroactive application when a new Global Play is added after a league already exists (Section 3).
