<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AssistantCoachController;
use App\Http\Controllers\Api\ConfigureController;
use App\Http\Controllers\Api\LeagueController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FormationController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\PlayController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\PlayGameModeController;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\SuggestionController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\PracticeTeamPlayerController;
use App\Http\Controllers\Api\GameController;
use App\Http\Responses\BaseResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DefensivePlayController;
use App\Http\Controllers\Api\DefensivePlayParameterController;
use App\Http\Controllers\Api\BenchPlayerController;
use App\Http\Controllers\Api\BroadCastScoreController;
use App\Http\Controllers\Api\MatchPlaysController;
use App\Http\Controllers\QBController;
use App\Http\Controllers\PersionalGroupingController;

use App\Http\Controllers\Api\WebQrController;
use App\Http\Controllers\Api\ConfigurationController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();

// });

// this data will add to the in the user controller
//  $headCoach = auth()->user(); // or User::find($headCoachId);

//     // 1. Create assistant coach user
//     $assistant = User::create([
//         'name' => $request->name,
//         'email' => $request->email,
//         'password' => bcrypt($request->password),
//         'role' => 'assistant_coach',
//         'head_coach_id' => $headCoach->id
//     ]);

//     // 2. Copy roles from head coach to assistant
//     $headCoachRoles = $headCoach->roles->pluck('name'); // use names, not IDs

//     $assistant->assignRole($headCoachRoles);




// use Illuminate\Support\Str;
// use Illuminate\Support\Facades\DB;

// Route::get('/api/mobile/generate-qr', function () {
//     $sessionId = Str::uuid(); // generate unique session ID

//     // Save in DB with status "pending"
//     DB::table('login_sessions')->insert([
//         'session_id' => $sessionId,
//         'status' => 'pending',
//         'created_at' => now(),
//     ]);

//     // The QR can contain a URL with session_id or just the session_id itself
//     return response()->json([
//         'qr_url' => url("/web-login?session_id={$sessionId}")
//     ]);
// });


Route::prefix('qb')->group(function () {

     Route::middleware(['auth:sanctum', 'permission:matchstart.scoreboard.edit'])->post('/scoreboard/broadcast', [BroadCastScoreController::class, 'scoreBoardBroadCastQB']);
     Route::middleware(['auth:sanctum', 'permission:matchstart.scoreboard.edit'])->post('/play/scoreboard/broadcast', [BroadCastScoreController::class, 'scoreBoardBroadCastPlay']);
    //Route::post('/scoreboard/broadcast', [BroadCastScoreController::class, 'scoreBoardBroadCastQB']);
});

// Device app endpoints (FOR APP)
Route::match(['get', 'post'], '/devices/login-with-code', [AuthController::class, 'loginDeviceWithCode']);

Route::get('/devices/logout/{id}', [WebQrController::class, 'logoutDeviceApplication']);
Route::get('/devices/session-status/{session_id}', [WebQrController::class, 'deviceSessionStatus']);
Route::get('/devices/active-match/{session_id}', [WebQrController::class, 'deviceActiveMatch']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/devices/update-info', [DeviceController::class, 'updateInfo']);
});


Route::middleware(['auth:sanctum', 'ensure.active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('userUpdate',[AuthController::class,'userUpdate']);
    Route::post('save-sport',[AuthController::class,'saveSport']);
    Route::post('change-password',[AuthController::class,'changePassword'])->name('password.change');
    Route::get('assistant-coaches', [AssistantCoachController::class, 'index'])->name('assistant-coaches.index')->middleware('permission:assistant_coach.view');
    Route::post('assistant-coaches', [AssistantCoachController::class, 'store'])->name('assistant-coaches.store')->middleware('permission:assistant_coach.manage');
    Route::get('assistant-coaches/{id}', [AssistantCoachController::class, 'show'])->name('assistant-coaches.show')->middleware('permission:assistant_coach.view');
    Route::put('assistant-coaches/{id}', [AssistantCoachController::class, 'update'])->name('assistant-coaches.update')->middleware('permission:assistant_coach.manage');
    Route::patch('assistant-coaches/{id}/status', [AssistantCoachController::class, 'updateStatus'])->name('assistant-coaches.update-status')->middleware('permission:assistant_coach.manage');
    Route::delete('assistant-coaches/{id}', [AssistantCoachController::class, 'destroy'])->name('assistant-coaches.destroy')->middleware('permission:assistant_coach.manage');


    Route::get('/sport',[SportController::class,'sport'])->name('sport');

    //  Leaque
    Route::post('/leaque-create',[SportController::class,'store'])->name('league.create')->middleware('permission:league.create');
    Route::get('/leaque',[SportController::class,'league'])->name('leaque')->middleware('permission:league.view');
    Route::get('/leaque-view/{id}',[SportController::class,'leagueView'])->name('leagueView')->middleware('permission:league.view');
    Route::post('/leaque-update/{id}',[SportController::class,'leagueUpdate'])->name('leagueUpdate')->middleware('permission:league.edit');
    Route::post('/update-leagueplayers/{id}',[SportController::class,'updateNumberOfPlayers'])->name('leagueUpdate')->middleware('permission:league.edit');
    Route::post('/leaque-update-points/{id}',[SportController::class,'leagueUpdatePoints'])->middleware('permission:league.edit');


    Route::get('/leaque-rule',[SportController::class,'leagueRule'])->name('leaque-rule')->middleware('permission:league.view');

    Route::post('/add-player',[PlayerController::class,'store'])->name('add.player')->middleware('permission:player.create');
    Route::post('/add-open-player',[PlayerController::class,'addOpenPlayer'])->middleware('permission:player.create');

    Route::post('/bench-players', [BenchPlayerController::class, 'store']);
    Route::put('/bench/{id}/update', [BenchPlayerController::class, 'rppUpdate']);

    Route::post('/shuffle-players', [BenchPlayerController::class, 'shufflePlayers']);
    Route::post('/opponent-bench-player-store', [BenchPlayerController::class, 'opponentBenchPlayerStore']);
    Route::get('/bench-players/{gameId}/{teamId}', [BenchPlayerController::class, 'index']);
    Route::get('/bench-players_count/{gameId}', [BenchPlayerController::class, 'getCount']);
    Route::get('/bench-opponent-players/{gameId}/{teamId}', [BenchPlayerController::class, 'getOpponentBenchPlayers']);
    Route::post('/create-my-team-play-mode', [BenchPlayerController::class, 'createMyTeamForPlayMode']);
    Route::post('/create-opponent-team-play-mode', [BenchPlayerController::class, 'createOpponentTeamForPlayMode']);
    Route::post('/add-opponent-package', [BenchPlayerController::class, 'addOpponentPackage']);
     Route::get('/get-opponent-packages/{gameId}/{teamId}', [BenchPlayerController::class, 'getOpponentTeamPackages']);

    Route::put('/update-player/{id}',[PlayerController::class,'update'])->name('update.player')->middleware('permission:player.edit');
    Route::put('/update-practice-player/{id}',[PlayerController::class,'updatePracticePlayer'])->name('update.practiceplayer')->middleware('permission:player.edit');
    Route::put('/team-players/{id}/ofp', [PlayerController::class, 'updateOFP'])->middleware('permission:player.edit');
    Route::get('/player-list',[PlayerController::class,'list'])->name('player.list')->middleware('permission:player.view');
    Route::post('/update-player/{id}',[PlayerController::class,'update'])->name('player.update')->middleware('permission:player.edit');
    Route::get('/delete-player/{id}/{team_id}',[PlayerController::class,'delete'])->name('player.delete')->middleware('permission:player.delete');
    Route::get('/delete-player-only/{id}/{team_id}',[PlayerController::class,'deletePlayer'])->name('player.delete.only')->middleware('permission:player.delete');

    Route::get('/view-player/{id}',[PlayerController::class,'view'])->name('player.view')->middleware('permission:player.view');

    Route::get('/dashboard',[SportController::class,'dashboard'])->name('dashboard');

    // Formation
    Route::post('/create-formation',[FormationController::class,'store'])->name('create-formation')->middleware('permission:formation.create');
    Route::get('/formation-view/{id}',[FormationController::class,'view'])->name('formation-view')->middleware('permission:formation.view');
    Route::get('/formation-list',[FormationController::class,'list'])->name('formation-list')->middleware('permission:formation.view');
    Route::post('/update-formation/{id}',[FormationController::class,'update'])->name('update-formation')->middleware('permission:formation.edit');
    Route::get('/delete-formation/{id}',[FormationController::class,'delete'])->middleware('permission:formation.delete');

    //profile
    Route::get('view-profile',[AuthController::class,'viewProfile'])->name('view-profile');
    Route::post('profile-update',[AuthController::class,'profileUpdate'])->name('profile-update');
    Route::post('change-password',[AuthController::class,'changePassword'])->name('profile-update');

    // upload Play
    Route::post('/uplaod-play',[PlayController::class,'store'])->name('uplaod-play')->middleware('permission:play.create');
    Route::post('/play-offensive-target-store',[PlayController::class,'playOffenseTargetStore'])->name('uplaod-play')->middleware('permission:play.edit');
    Route::get('/get-play-offensive-target/{id}',[PlayController::class,'getOffensiveTargetsByPlay'])->name('uplaod-play')->middleware('permission:play.view');

    Route::get('/upload-play-list',[PlayController::class,'index'])->name('upload-play-list')->middleware('permission:play.view');
    Route::get('/match-plays', [MatchPlaysController::class, 'index'])->middleware('permission:play.view');
    Route::get('/delete-play/{id}',[PlayController::class,'delete'])->name('delete-play')->middleware('permission:play.delete');
    Route::get('/get-offense-target-play/{id}',[PlayController::class,'getTargetOffensePosition'])->name('delete-play')->middleware('permission:play.view');
    Route::get('/edit-play/{id}',[PlayController::class,'editPlay'])->name('edit-play')->middleware('permission:play.view');
    Route::get('/duplicate-play/{id}',[PlayController::class,'duplicatePlay'])->name('edit-play')->middleware('permission:play.duplicate');
    Route::get('/delete-play-results/{id}', [PlayController::class, 'deletePlayResults'])->middleware('permission:play.delete');



    Route::post('/update-play/{id}',[PlayController::class,'update'])->name('update-play')->middleware('permission:play.edit');

    Route::get('/offensive-positions', [PlayController::class, 'getOffensivePositions'])->name('offensive-positions');
    Route::get('/defensive-positions', [PlayController::class, 'getDefensivePositions'])->name('defensive-positions');
    Route::get('/play-results', [PlayController::class, 'getPlayResult'])->middleware('permission:play.view');

    Route::post('/play-results-add', [PlayController::class, 'addPlayResult']);



    // Team
    Route::post('create-team',[TeamController::class,'store'])->middleware('permission:team.create');
    Route::get('team-list',[TeamController::class,'index'])->middleware('permission:team.view');
    Route::get('view-team/{id}',[TeamController::class,'view'])->middleware('permission:team.view');
    Route::get('team/{id}/players-paginated', [TeamController::class, 'paginatedTeamPlayers'])->middleware('permission:team.view');
    Route::get('practice-team/{id}/players-paginated', [TeamController::class, 'paginatedPracticeTeamPlayers'])->middleware('permission:team.view');
    Route::get('practice-team-list/{id}',[TeamController::class,'practiceTeamList'])->middleware('permission:team.view');

    Route::post('practice-update-team/{id}',[PracticeTeamPlayerController::class,'update'])->middleware('permission:team.edit');
    Route::post('update-team/{id}',[TeamController::class,'update'])->middleware('permission:team.edit');
    Route::get('team-list-by-league/{id}',[TeamController::class,'teamListByLeague'])->middleware('permission:team.view');
    Route::get('team-list-by-play-mode/{id}',[TeamController::class,'teamListForPlayMode'])->middleware('permission:team.view');

    Route::post('/configure-player',[ConfigureController::class,'store'])->middleware('permission:configure.manage');
    Route::post('/configure-player-visiting',[ConfigureController::class,'storevisiting'])->middleware('permission:configure.manage');
    Route::get('/configure-player-view',[ConfigureController::class,'view'])->middleware('permission:configure.manage');

    Route::post('/configure-formation',[ConfigureController::class,'configureFormation'])->middleware('permission:configure.manage');
    Route::get('/configure-formation-view',[ConfigureController::class,'configureFormationView'])->middleware('permission:configure.manage');

    Route::post('/configure-play',[ConfigureController::class,'configurePlay'])->middleware('permission:configure.manage');
    Route::post('/configure-defensive-play',[ConfigureController::class,'configureDefensivePlay'])->middleware('permission:configure.manage');
    Route::get('/configure-play-view',[ConfigureController::class,'configurePlayView'])->middleware('permission:configure.manage');
    Route::get('/configure-defensive-play-view',[ConfigureController::class,'configurePlayDefensiveView'])->middleware('permission:configure.manage');
    // Route::post('/defensive-plays', [DefensivePlayController::class, 'store']);
    Route::post('/defensive-plays', [DefensivePlayController::class, 'store'])->middleware('permission:defensive_play.create');
    Route::post('/defensive-plays-parameters', [DefensivePlayParameterController::class, 'store'])->middleware('permission:defensive_play_parameter.create');
    Route::get('/defensive-plays-parameters/{id}', [DefensivePlayParameterController::class, 'index'])->middleware('permission:defensive_play_parameter.view');
    Route::get('/delete-defensive-play/{id}', [DefensivePlayController::class, 'delete'])->middleware('permission:defensive_play.delete');
    Route::get('/delete-play-parameters/{id}', [DefensivePlayParameterController::class, 'delete'])->middleware('permission:defensive_play_parameter.delete');
    Route::get('/edit-defensive-play-parameter/{id}', [DefensivePlayParameterController::class, 'edit'])->middleware('permission:defensive_play_parameter.view');
    Route::put('/update-defensive-play-parameter/{id}',[DefensivePlayParameterController::class,'update'])->name('update.defensive-player')->middleware('permission:defensive_play_parameter.edit');
    Route::get('/upload-defensive-play-list',[DefensivePlayController::class,'index'])->name('upload-play-list')->middleware('permission:defensive_play.view');
    Route::get('/edit-defensive-play/{id}',[DefensivePlayController::class,'editDefensivePlay'])->name('edit-defensive-play')->middleware('permission:defensive_play.view');
    Route::post('/update-defensive-play/{id}',[DefensivePlayController::class,'update'])->name('update.defensive-player')->middleware('permission:defensive_play.edit');
    Route::get('/duplicate-defensive-play/{id}',[DefensivePlayController::class,'duplicateDefensivePlay'])->name('edit-play')->middleware('permission:defensive_play.duplicate');
    Route::controller(GameController::class)->group(function () {
            Route::get('/games/id', 'index')->middleware('permission:game.view');
            Route::post('/games', 'store')->middleware('permission:game.create');
            Route::post('/games/{id}/duplicate', 'duplicate')->middleware('permission:game.duplicate');
            Route::get('/game/{id}', 'show')->middleware('permission:game.view');
            Route::get('/game/{id}/opponents_my', 'getOpponentMyTeamPlayers')->middleware('permission:game.view');
            Route::get('/leagues/{leagueId}/opponent-teams', 'opponentTeams')->middleware('permission:game.view');
            Route::get('/leagues/{leagueId}/scheduled-dates', 'scheduledDatesByLeague')->middleware('permission:game.view');
            Route::get('/games/league/{leagueId}', 'getByLeague')->middleware('permission:game.view');
            Route::get('/leagues-upcoming-matches', 'leaguesUpcomingMatches')->middleware('permission:game.view');
            Route::post('/penalities', 'Penalities')->middleware('permission:matchstart.penalty.manage');
            Route::get('/penalty-list', 'penaltyList')->middleware('permission:matchstart.penalty.manage');
            Route::get('/delete-game/{id}','delete')->middleware('permission:game.delete');
    });

    Route::controller(SubscriptionPlanController::class)->group(function () {
        Route::get('/subscription-plan', 'subscriptionPlan');
        Route::post('/addSubscription', 'addSubscription');
        Route::post('/updateSubscription', 'updateSubscription');
        Route::get('/cancel-subscription', 'cancelSubscription');
        Route::get('/getPlane','getPlane');
    });

    Route::controller(PlayGameModeController::class)->group(function () {
       Route::post('/start-game-mode', 'startGameGode')->middleware('permission:matchstart.scoreboard.edit');
       Route::post('/add-points-update-state', 'addPoints')->middleware('permission:matchstart.scoreboard.edit');
       Route::post('/add-play-game-log', 'addPointsObject')->middleware('permission:matchstart.scoreboard.edit');

    });

   Route::post('/play/show-yardage/assistant-coach', [BroadCastScoreController::class, 'yardagePlaytoAssistant'])->middleware('permission:matchstart.scoreboard.edit');
   Route::post('/assistant-coach/system-suggestion/broadcast', [BroadCastScoreController::class, 'systemSuggestionToHeadCoach'])->middleware('permission:matchstart.system_suggestion.use');
   Route::post('/scoreboard/broadcast', [BroadCastScoreController::class, 'scoreBoardBroadCast'])->middleware('permission:matchstart.scoreboard.edit');
   Route::post('/practice/scoreboard/broadcast', [BroadCastScoreController::class, 'practiceScoreBoardBroadCast'])->middleware('permission:matchstart.scoreboard.edit');
   Route::post('logout-qb', [WebQrController::class, 'logoutQb'])->middleware('permission:qb.manage');
   Route::get('/scoreboard', [BroadCastScoreController::class, 'getWebSocketScoreBoard'])->middleware('permission:matchstart.scoreboard.view');
   Route::get('/practice-scoreboard', [BroadCastScoreController::class, 'getPracticeWebSocketScoreBoard'])->middleware('permission:matchstart.scoreboard.view');
   Route::get('/delete-scoreboard/{gameId}',[BroadCastScoreController::class,'delete'])->name('deleteScoreBoard')->middleware('permission:matchstart.scoreboard.edit');
   Route::get('/practice/delete-scoreboard/{gameId}',[BroadCastScoreController::class,'deletePractice'])->name('deletePracticeScoreBoard')->middleware('permission:matchstart.scoreboard.edit');

  Route::post('/persional-groups', [PersionalGroupingController::class, 'storeAllGroups'])->middleware('permission:team_group.create');
  Route::post('/personal-groupings/{group}/plays/sync', [PersionalGroupingController::class, 'syncPlays'])->middleware('permission:team_group.edit');
  Route::post('/update/{group}/group', [PersionalGroupingController::class, 'updateGroup'])->middleware('permission:team_group.edit');


  // routes/api.php
  Route::get('/personal-groupings/{group}/plays', [PersionalGroupingController::class, 'getPlays'])->middleware('permission:team_group.view');
  Route::get('/personal-groupings/{group}/roster-repair-missing', [PersionalGroupingController::class, 'rosterRepairMissing'])->middleware('permission:team_group.view');
  Route::get('/delete/{group}/group', [PersionalGroupingController::class, 'deleteGroup'])->middleware('permission:team_group.delete');

  Route::get('/persional-groups-players', [PersionalGroupingController::class, 'getGroupsByTeamAndGame'])->middleware('permission:team_group.view');

  // Team Groups
  Route::get('/teams/{teamId}/groups', [\App\Http\Controllers\TeamGroupController::class, 'index'])->whereNumber('teamId')->middleware('permission:team_group.view');
  Route::post('/teams/{teamId}/groups', [\App\Http\Controllers\TeamGroupController::class, 'store'])->whereNumber('teamId')->middleware('permission:team_group.create');
  Route::put('/groups/{id}', [\App\Http\Controllers\TeamGroupController::class, 'update'])->whereNumber('id')->middleware('permission:team_group.edit');
  Route::delete('/groups/{id}', [\App\Http\Controllers\TeamGroupController::class, 'destroy'])->whereNumber('id')->middleware('permission:team_group.delete');
  Route::get('/teams/{teamId}/players', [\App\Http\Controllers\TeamGroupController::class, 'players'])->whereNumber('teamId')->middleware('permission:team_group.view');
  Route::post('/games/{gameId}/import-team-groups', [\App\Http\Controllers\TeamGroupController::class, 'importToGame'])->whereNumber('gameId')->middleware('permission:team_group.create');


   Route::prefix('leagues')->group(function () {
        Route::prefix('/{league}/matches')->group(function () {
            Route::get('/', [MatchController::class, 'index'])->middleware('permission:game.view');
            Route::put('/{match}/', [MatchController::class, 'update'])->middleware('permission:game.edit');
            Route::prefix('/{match}/logs')->group(function () {
                Route::get('/', [LogController::class, 'index'])->middleware('permission:game.view');
            });
        });
    });

    Route::prefix('leagues')->group(function () {
        Route::put('/{league}', [LeagueController::class, 'update'])->middleware('permission:league.edit');
        Route::get('/{league}/get-suggested-plays', [SuggestionController::class, 'getSuggestedPlays'])->middleware('permission:matchstart.system_suggestion.use');

        // Device Management
        Route::prefix('/{league}/devices')->group(function () {
            Route::get('/', [DeviceController::class, 'index'])->middleware('permission:device.view');
            Route::post('/', [DeviceController::class, 'store'])->middleware('permission:device.manage');
            Route::put('/{device}', [DeviceController::class, 'update'])->middleware('permission:device.manage');
            Route::delete('/{device}', [DeviceController::class, 'destroy'])->middleware('permission:device.manage');
            Route::post('/{device}/scan-qr', [DeviceController::class, 'scanQr'])->middleware('permission:device.manage');
            Route::post('/{device}/logout', [DeviceController::class, 'logout'])->middleware('permission:device.manage');
        });
    });

    Route::get('/configurations', [ConfigurationController::class, 'index']);
    Route::post('/configurations', [ConfigurationController::class, 'store']);
    Route::put('/configurations', [ConfigurationController::class, 'update']);
    Route::get('/configurations/{key}', [ConfigurationController::class, 'show']);
});

// start-game-mode
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/signup-request', [AuthController::class, 'signupRequest']);
Route::get('/approve-user/{id}', [AuthController::class, 'approveUser'])->middleware(['auth:sanctum', 'permission:pending_user.approve']);
Route::post('/verify-code', [AuthController::class, 'verifyCode']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forget-password',[AuthController::class,'forgotPassword'])->name('forget.change');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
