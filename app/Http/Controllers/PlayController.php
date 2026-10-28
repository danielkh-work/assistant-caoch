<?php

namespace App\Http\Controllers;

use App\Models\DefensivePosition;
use App\Models\League;
use App\Models\OffensivePosition;
use App\Models\Play;
use App\Models\PlayTargetDefensivePlayer;
use App\Models\PlayTargetOffensivePlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;

class PlayController extends Controller
{
      /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->listPlays($request, false);
    }

    /**
     * Same listing, but scoped to Global Plays only - backs the "Global Plays"
     * sidebar submenu.
     */
    public function indexGlobal(Request $request)
    {
        return $this->listPlays($request, true);
    }

    private function listPlays(Request $request, bool $onlyGlobal)
    {
        $query = Play::with(['roles', 'league'])->orderBy('id', 'desc');
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }
        if ($onlyGlobal || $request->input('scope') === 'global') {
            $query->where('is_global', true);
        } elseif ($request->input('scope') === 'league') {
            $query->where('is_global', false);
        }
        if ($request->filled('starter')) {
            $query->where('is_starter_play', $request->input('starter') === '1');
        }
        $this->applyPlayValueFilters($query, $request);
        $data = $query->get();
        if ($request->ajax()) {
            $historyCounts = DB::table('play_game_logs')
                ->whereIn('play_id', $data->pluck('id'))
                ->selectRaw('play_id, count(*) as cnt')
                ->groupBy('play_id')
                ->pluck('cnt', 'play_id');

            return DataTables::of($data)
                ->addIndexColumn()
                  ->addColumn('roles', function($row) {
                            return $row->roles->pluck('name')->implode(', ');
                    })
                    ->addColumn('league', function($row){
                        return $row->league->title ?? '<span class="text-muted">&mdash;</span>';
                    })
                    ->addColumn('action', function($row) use ($historyCounts){
                    $editUrl = route('play.edit', ['id' => $row->id]);
                    $safeName = htmlspecialchars($row->play_name, ENT_QUOTES);
                    $historyCount = (int) ($historyCounts[$row->id] ?? 0);

                    return '
                        <a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1">Edit</a>
                        <button type="button" class="btn btn-danger btn-sm delete-play-btn" data-id="' . $row->id . '" data-name="' . $safeName . '" data-history-count="' . $historyCount . '">Delete</button>
                    ';
                    })
                    ->addColumn('starter', function($row){
                        if (!$row->is_global) {
                            return '<span class="text-muted">&mdash;</span>';
                        }
                        $checked = $row->is_starter_play ? 'checked' : '';
                        return '<label class="pv-switch"><input type="checkbox" class="starter-toggle" data-id="' . $row->id . '" ' . $checked . '><span class="pv-switch__slider"></span></label>';
                    })
                ->rawColumns(['action', 'starter', 'league'])
                ->make(true);
        }
        return view($onlyGlobal ? 'plays.global' : 'plays.index');
    }
    public function create(){

       $league = League::all();
       $offensive_position = OffensivePosition::all(['id', 'name']);
       $defensive_positions = DefensivePosition::all(['id', 'name']);
        $roles =  Role::all();
       return view('plays.create',compact('league','offensive_position','defensive_positions','roles'));
    }
    public function edit($id)
    {
        $play = Play::with(['offensivePositions','deffensivePositions'])->findOrFail($id);
        $league = League::all();
        $offensive_position = OffensivePosition::all(['id', 'name']);
        $defensive_positions = DefensivePosition::all(['id', 'name']);

        return view('plays.edit', compact('play', 'league', 'offensive_position', 'defensive_positions'));
    }

     public function update(Request $request, $id)
    {
       
 
        DB::beginTransaction();
      
            $play = Play::findOrFail($id);
            $play->play_name = $request->play_name;
            if ($request->filled('league_id')) {
                $play->league_id = $request->league_id;
                $play->is_global = false;
            } else {
                $play->league_id = null;
                $play->is_global = true;
            }
            $play->min_expected_yard = $request->min_expected_yard;
            $play->preferred_down = is_array($request->preferred_down)
                ? implode(',', $request->preferred_down)
                : $request->preferred_down;
            $play->strategies = is_array($request->strategies)
                ? implode(',', $request->strategies)
                : $request->strategies;
            $play->possession = $request->possession;
            $play->description = $request->description;
            $play->read_1 = $request->read_1;
            $play->read_2 = $request->read_2;
            foreach (['hmark_left', 'hmark_center', 'hmark_right'] as $hmarkField) {
                if ($request->hasFile($hmarkField)) {
                    $play->{$hmarkField} = uploadImage($request->file($hmarkField), 'public', $play->{$hmarkField});
                }
            }
            if ($request->hasFile('video')) {
                $videoPath = uploadImage($request->file('video'), 'public/uploads/videos');
                $play->video_path = $videoPath;
            }

            $play->save();
            PlayTargetOffensivePlayer::where('play_id', $play->id)->delete();
            if (is_array($request->offensive)) {
                foreach ($request->offensive as $position => $value) {
                    if ($value === null) {
                        continue; // Skip this entry if the value is null
                    }
                    PlayTargetOffensivePlayer::create([
                        'play_id' => $play->id,
                        'offensive_position_id' => $position,
                        'strength' => $value,
                    ]);
                }
            }

            PlayTargetDefensivePlayer::where('play_id', $play->id)->delete();
            if (is_array($request->defensive)) {
                foreach ($request->defensive as $position => $value) {
                     if ($value === null) {
                        continue; // Skip this entry if the value is null
                    }
                    PlayTargetDefensivePlayer::create([
                        'play_id' => $play->id,
                        'defensive_position_id' => $position,
                        'strength' => $value,
                    ]);
                }
            }

            DB::commit();
            $play->roles()->sync($request->role_id); // assign to multiple roles
             DB::commit();
            return redirect()->route('play.index');
        // } catch (\Throwable $e) {
        //     DB::rollBack();
        //    dd($e->getMessage());
        // }
    }

    public function destroy(Request $request, $id)
    {
        $play = Play::findOrFail($id);

        DB::transaction(function () use ($play) {
            DB::table('play_game_logs')->where('play_id', $play->id)->delete();
            $play->delete();
        });

        if ($request->ajax() || $request->wantsJson()) {
            // A redirect response here would make an XHR DELETE's browser-side
            // redirect-follow re-issue as DELETE against the Location URL (which
            // doesn't support DELETE) - the play would already be gone, but the
            // request would still come back as an error. AJAX callers get a plain
            // JSON response instead; classic form submits keep the redirect/flash.
            return response()->json(['message' => 'Play deleted successfully.']);
        }

        return redirect()->route('play.index')->with('success', 'Play deleted successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');
        $deletedCount = DB::transaction(function () use ($ids) {
            DB::table('play_game_logs')->whereIn('play_id', $ids)->delete();
            return Play::whereIn('id', $ids)->delete();
        });

        $message = $deletedCount . ($deletedCount === 1 ? ' play deleted.' : ' plays deleted.');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message, 'deleted' => $deletedCount]);
        }

        return redirect()->route('play.index')->with('success', $message);
    }

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

    public function bulkToggleStarter(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'starter' => 'required|boolean',
        ]);

        $updated = Play::whereIn('id', $request->input('ids'))
            ->where('is_global', true)
            ->update(['is_starter_play' => $request->boolean('starter')]);

        return response()->json(['updated' => $updated]);
    }

    public function globalIds(Request $request)
    {
        $query = Play::where('is_global', true);

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }

        if ($request->filled('search')) {
            $query->where('play_name', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('starter')) {
            $query->where('is_starter_play', $request->input('starter') === '1');
        }

        $this->applyPlayValueFilters($query, $request);

        return response()->json(['ids' => $query->pluck('id')]);
    }

    /**
     * Shared by index() (the DataTable) and globalIds() (what "Apply to all matching
     * Global Plays across all pages" expands to) - they must stay in lockstep, or
     * that action would apply to a different set of plays than the admin is looking at.
     */
    private function applyPlayValueFilters($query, Request $request): void
    {
        $selectedStrategies = array_filter(array_map('trim', explode(',', (string) $request->input('strategies', ''))));
        if (!empty($selectedStrategies)) {
            $query->where(function ($q) use ($selectedStrategies) {
                foreach ($selectedStrategies as $strategy) {
                    $q->orWhere('strategies', 'like', '%' . addcslashes($strategy, '%_\\') . '%');
                }
            });
        }

        $selectedDowns = array_filter(array_map('trim', explode(',', (string) $request->input('preferred_down', ''))));
        if (!empty($selectedDowns)) {
            $query->where(function ($q) use ($selectedDowns) {
                foreach ($selectedDowns as $down) {
                    $q->orWhere('preferred_down', 'like', '%' . addcslashes($down, '%_\\') . '%');
                }
            });
        }

        $selectedYardages = array_filter(array_map('trim', explode(',', (string) $request->input('yardage_gain', ''))));
        if (!empty($selectedYardages)) {
            $query->whereIn('min_expected_yard', $selectedYardages);
        }

        $selectedTargets = array_filter(array_map('trim', explode(',', (string) $request->input('play_target', ''))));
        if (!empty($selectedTargets)) {
            $query->whereIn('possession', $selectedTargets);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $play = new Play();
            $play->play_name = $request->play_name;
            $play->league_id = null;
            $play->is_global = true;
            $play->play_type = 1;
            $play->quarter = 1;
            $play->created_by = 'admin';
            $play->created_by_user_id = auth()->id();
            $play->zone_selection = 1;
            $play->min_expected_yard = $request->min_expected_yard;
            $play->max_expected_yard = 1;
          
            $play->pre_snap_motion = 1;
            $play->play_action_fake = 1;
            
            if (is_array($request->preferred_down)) {
                $play->preferred_down = implode(',', $request->preferred_down);
            } else {
                // If it's a single value or null, just save it directly
                $play->preferred_down = $request->preferred_down;
            }
             if (is_array($request->strategies)) {
                $play->strategies = implode(',', $request->strategies);
            } else {
                // If it's a single value or null, just save it directly
                $play->strategies = $request->strategies;
            }

            $play->possession = $request->possession;
            $play->description = $request->description;
            $play->read_1 = $request->read_1;
            $play->read_2 = $request->read_2;
            $play->position_status = 2;
            $play->video_path = 'video path';
            // Upload image
            if ($request->hasFile('image')) {
                $imagePath = uploadImage($request->file('image'), 'public/uploads/public');
                $play->image = $imagePath;
            }
            // Upload video (optional)
            if ($request->hasFile('video')) {
                $videoPath = uploadImage($request->file('video'), 'public/uploads/videos');
                $play->video_path = $videoPath;
            }
            $play->save();

             if ($request->has('role_id') && is_array($request->role_id)) {
                 $play->roles()->sync($request->role_id);
              }
            
          
             
            if (is_array($request->offensive)) {
                $offensivePositions = OffensivePosition::pluck('id', 'name')->toArray();
                foreach ($request->offensive as $position => $value) {
                  if ($value === null) {
                        continue; // Skip this entry if the value is null
                    }
                    PlayTargetOffensivePlayer::create([
                        'play_id' => $play->id,
                        'offensive_position_id' => $position,
                        'strength' => $value, // or other columns if needed
                    ]);
                }
            }

           
            if (is_array($request->defensive)) {
                $defensivePositions = DefensivePosition::pluck('id', 'name')->toArray();
                foreach ($request->defensive as $position => $value) {
                    if ($value === null) {
                            continue; // Skip this entry if the value is null
                        }
                    PlayTargetDefensivePlayer::create([
                        'play_id' => $play->id,
                        'defensive_position_id' => $position,
                        'strength' => $value,
                    ]);
                }
            }

            DB::commit();
           return redirect()->route('play.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            dd($e->getMessage());
        }

    }
}
