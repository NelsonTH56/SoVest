<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StockController;

// We're not really using routes here we just want to redirect to main.php

Route::get('/', [MainController::class, 'index'])->name('landing');
Route::get('/about', [MainController::class, 'about'])->name('about');

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/stocks/{symbol}', [StockController::class, 'show'])->name('stocks.show')->where('symbol', '[A-Za-z]{1,5}');
Route::post('/prediction/vote', [PredictionController::class, 'vote'])->name('prediction.vote');

// Authentication routes with rate limiting (5 attempts per minute)
Route::middleware('throttle:5,1')->group(function () {
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register.form');
    Route::post('/register/submit', [AuthController::class, 'register'])->name('register.submit');
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login/submit', [AuthController::class, 'login'])->name('login.submit');
});

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::patch('/user/update-bio', [UserController::class, 'updateBio'])->name('user.updateBio');


Route::get('/home', [UserController::class, 'home'])->name('user.home')->middleware('auth');
Route::get('/account', [UserController::class, 'account'])->name('user.account')->middleware('auth');
Route::get('/leaderboard', [UserController::class, 'leaderboard'])->name('user.leaderboard')->middleware('auth');
Route::post('/profile/upload-photo', [UserController::class, 'uploadPhoto'])->name('user.profile.uploadPhoto');
Route::controller(PredictionController::class)->group(function () {
    Route::get('/predictions', 'index')->name('predictions.index')->middleware('auth');
    Route::get('/predictions/view/{id}', 'view')->name('predictions.view');
    Route::get('/predictions/trending', 'trending')->name('predictions.trending');
    Route::get('/predictions/create', 'create')->name('predictions.create')->middleware('auth');
    Route::post('/predictions/store', 'store')->name('predictions.store')->middleware('auth');
    Route::get('/predictions/edit/{id}', 'edit')->name('predictions.edit')->middleware('auth')->middleware('prediction.owner');
    Route::post('/predictions/update/{id}', 'update')->name('predictions.update')->middleware('auth')->middleware('prediction.owner');
    Route::post('/predictions/delete/{id}', 'delete')->name('predictions.delete')->middleware('auth')->middleware('prediction.owner');
    //Route::post('/predictions/vote/{id}', 'vote')->name('predictions.vote')->middleware('auth');  ORIGINAL
    Route::post('/predictions/vote/{id}', [PredictionController::class, 'vote'])->middleware('auth'); //SUGGESTED SOLUTION
    Route::get('/predictions/{id}/vote-counts', function ($id) {
        $upvotes = \App\Models\PredictionVote::where('prediction_id', $id)->where('vote_type', 'upvote')->count();
        $downvotes = \App\Models\PredictionVote::where('prediction_id', $id)->where('vote_type', 'downvote')->count();
        $netVotes = $upvotes - $downvotes;
        return response()->json([
            'success' => true,
            'upvotes' => $upvotes,
            'downvotes' => $downvotes,
            'netvotes' => $netVotes
        ]);
    });
    
});

// API routes with rate limiting
// 60 requests per 1 minute for general API endpoints
Route::prefix('api')->middleware(['api', 'throttle:120,1'])->name('api.')->group(function () {
    Route::match(['GET', 'POST'], '/predictions', [PredictionController::class, 'apiHandler'])->name('predictions');
    Route::post('/predictions/create', [PredictionController::class, 'store'])->name('predictions.create');
    Route::post('/predictions/update', [PredictionController::class, 'update'])->name('predictions.update')->middleware('prediction.owner');
    Route::delete('/predictions/delete/{id}', [PredictionController::class, 'delete'])->name('predictions.delete');
    Route::get('/predictions/{id}', [PredictionController::class, 'view'])->name('predictions.view');
    Route::get('/predictions/get', [PredictionController::class, 'apiGetPrediction'])->name('predictions.get');
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/search_stocks', [SearchController::class, 'searchStocks'])->name('search.stocks');
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');
    Route::post('/search/save', [SearchController::class, 'saveSearch'])->name('search.save');
    Route::post('/search/clear-history', [SearchController::class, 'clearHistory'])->name('search.clearHistory');
    Route::post('/search/remove-saved', [SearchController::class, 'removeSavedSearch'])->name('search.removeSaved');
    Route::post('/fetch_stock_price', [SearchController::class, 'fetchStockPrice'])->name('fetch.stock.price');
    Route::get('/stocks', [SearchController::class, 'stocks'])->name('stocks');
    Route::get('/stocks/{symbol}', [SearchController::class, 'getStock'])->name('stocks.get')
        ->where('symbol', '[A-Z]{1,5}');
    Route::get('/stocks/{symbol}/price', [SearchController::class, 'getStockPrice'])->name('stocks.price')
        ->where('symbol', '[A-Z]{1,5}');
});