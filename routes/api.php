<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfigureController;
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
use App\Http\Controllers\Api\GameController;
use App\Http\Responses\BaseResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DefensivePlayController;
use App\Http\Controllers\Api\DefensivePlayParameterController;
use App\Http\Controllers\Api\BenchPlayerController;





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
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('userUpdate',[AuthController::class,'userUpdate']);
    Route::post('save-sport',[AuthController::class,'saveSport']);
    Route::post('change-password',[AuthController::class,'changePassword'])->name('password.change');
    Route::get('/sport',[SportController::class,'sport'])->name('sport');

    //  Leaque
    Route::post('/leaque-create',[SportController::class,'store'])->name('league.create');
    Route::get('/leaque',[SportController::class,'league'])->name('leaque');
    Route::get('/leaque-view/{id}',[SportController::class,'leagueView'])->name('leagueView');
    Route::post('/leaque-update/{id}',[SportController::class,'leagueUpdate'])->name('leagueUpdate');


    Route::get('/leaque-rule',[SportController::class,'leagueRule'])->name('leaque-rule');

    Route::post('/add-player',[PlayerController::class,'store'])->name('add.player');
    Route::post('/bench-players', [BenchPlayerController::class, 'store']);
    Route::get('/bench-players/{gameId}/{teamId}', [BenchPlayerController::class, 'index']);
    Route::put('/update-player/{id}',[PlayerController::class,'update'])->name('update.player');
    Route::get('/player-list',[PlayerController::class,'list'])->name('player.list');
    Route::post('/update-player/{id}',[PlayerController::class,'update'])->name('player.update');
    Route::get('/delete-player/{id}/{team_id}',[PlayerController::class,'delete'])->name('player.delete');
    Route::get('/delete-player-only/{id}/{team_id}',[PlayerController::class,'deletePlayer'])->name('player.delete.only');
    
    Route::get('/view-player/{id}',[PlayerController::class,'view'])->name('player.view');

    Route::get('/dashboard',[SportController::class,'dashboard'])->name('dashboard');

    // Formation
    Route::post('/create-formation',[FormationController::class,'store'])->name('create-formation');
    Route::get('/formation-view/{id}',[FormationController::class,'view'])->name('formation-view');
    Route::get('/formation-list',[FormationController::class,'list'])->name('formation-list');
    Route::post('/update-formation/{id}',[FormationController::class,'update'])->name('update-formation');
    Route::get('/delete-formation/{id}',[FormationController::class,'delete']);

    //profile
    Route::get('view-profile',[AuthController::class,'viewProfile'])->name('view-profile');
    Route::post('profile-update',[AuthController::class,'profileUpdate'])->name('profile-update');
    Route::post('change-password',[AuthController::class,'changePassword'])->name('profile-update');
  
    // upload Play
    Route::post('/uplaod-play',[PlayController::class,'store'])->name('uplaod-play');
    Route::get('/upload-play-list',[PlayController::class,'index'])->name('upload-play-list');
    Route::get('/delete-play/{id}',[PlayController::class,'delete'])->name('delete-play');
    Route::get('/edit-play/{id}',[PlayController::class,'editPlay'])->name('edit-play');
    Route::post('/update-play/{id}',[PlayController::class,'update'])->name('update-play');
    
    Route::get('/offensive-positions', [PlayController::class, 'getOffensivePositions'])->name('offensive-positions');
    Route::get('/defensive-positions', [PlayController::class, 'getDefensivePositions'])->name('defensive-positions');


    // Team
    Route::post('create-team',[TeamController::class,'store']);
    Route::get('team-list',[TeamController::class,'index']);
    Route::get('view-team/{id}',[TeamController::class,'view']);
    Route::post('update-team/{id}',[TeamController::class,'update']);
    Route::get('team-list-by-league/{id}',[TeamController::class,'teamListByLeague']);

    Route::post('/configure-player',[ConfigureController::class,'store']);
    Route::post('/configure-player-visiting',[ConfigureController::class,'storevisiting']);
    Route::get('/configure-player-view',[ConfigureController::class,'view']);

    Route::post('/configure-formation',[ConfigureController::class,'configureFormation']);
    Route::get('/configure-formation-view',[ConfigureController::class,'configureFormationView']);
   
    Route::post('/configure-play',[ConfigureController::class,'configurePlay']);
    Route::post('/configure-defensive-play',[ConfigureController::class,'configureDefensivePlay']);
    Route::get('/configure-play-view',[ConfigureController::class,'configurePlayView']);
    Route::get('/configure-defensive-play-view',[ConfigureController::class,'configurePlayDefensiveView']);
    // Route::post('/defensive-plays', [DefensivePlayController::class, 'store']);
    Route::post('/defensive-plays', [DefensivePlayController::class, 'store']);
    Route::post('/defensive-plays-parameters', [DefensivePlayParameterController::class, 'store']);
    Route::get('/defensive-plays-parameters/{id}', [DefensivePlayParameterController::class, 'index']);
    
    Route::get('/upload-defensive-play-list',[DefensivePlayController::class,'index'])->name('upload-play-list');
    Route::get('/edit-defensive-play/{id}',[DefensivePlayController::class,'editDefensivePlay'])->name('edit-defensive-play');
    Route::put('/update-defensive-play/{id}',[DefensivePlayController::class,'update'])->name('update.defensive-player');
    Route::controller(GameController::class)->group(function () {
            Route::get('/games/id', 'index');                  
            Route::post('/games', 'store');
            Route::get('/game/{id}', 'show');     
               Route::get('/game/{id}/opponents_my', 'getOpponentMyTeamPlayers');          
            Route::get('/games/league/{leagueId}', 'getByLeague');                
    });

    Route::controller(SubscriptionPlanController::class)->group(function () {
        Route::get('/subscription-plan', 'subscriptionPlan');
        Route::post('/addSubscription', 'addSubscription');
        Route::post('/updateSubscription', 'updateSubscription');
        Route::get('/cancel-subscription', 'cancelSubscription');
        Route::get('/getPlane','getPlane');
    });

    Route::controller(PlayGameModeController::class)->group(function () {
       Route::post('/start-game-mode', 'startGameGode');
       Route::post('/add-points-update-state', 'addPoints');
    });

    Route::prefix('leagues')->group(function () {
        Route::prefix('/{league}/matches')->group(function () {
            Route::get('/', [MatchController::class, 'index']);
            Route::put('/{match}/', [MatchController::class, 'update']);
            Route::prefix('/{match}/logs')->group(function () {
                Route::get('/', [LogController::class, 'index']);
            });
        });
    });

    Route::prefix('leagues')->group(function () {
        Route::get('/{league}/get-suggested-plays', [SuggestionController::class, 'getSuggestedPlays']);
    });
});

// start-game-mode
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forget-password',[AuthController::class,'forgotPassword'])->name('forget.change');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');


