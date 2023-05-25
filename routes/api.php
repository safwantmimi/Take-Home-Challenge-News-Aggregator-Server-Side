<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NewsApiController;
use App\Http\Controllers\UserController;

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
$middleware = [];

if (\Request::header('X-XSRF-TOKEN')) {
    $middleware = array_merge(['auth:sanctum']);
}
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/updateProfile', [UserController::class, 'update']);
    Route::post('/article_viewed', [NewsApiController::class, 'article_viewed']);

});

Route::group(['middleware' => $middleware], function () { // should work for both authenticated and not authenticated users
    Route::get('/news', [NewsApiController::class, 'index'])->middleware(["cors"]);
    Route::get('/latest', [NewsApiController::class, 'latest'])->middleware("cors");
    Route::get('/weekly', [NewsApiController::class, 'weekly'])->middleware("cors");
});

Route::get('/categories', [NewsApiController::class, 'categories'])->middleware("cors");
Route::get('/authors', [NewsApiController::class, 'authors'])->middleware("cors");
Route::get('/sources', [NewsApiController::class, 'sources'])->middleware("cors");