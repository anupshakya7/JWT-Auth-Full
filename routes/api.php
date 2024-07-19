<?php

use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api'], function ($routes) {
    Route::post('/register', [UserController::class,'register']);
    Route::post('/login', [UserController::class,'login']);
    Route::get('/logout', [UserController::class,'logout']);
    Route::get('/profile', [UserController::class,'profile']);
    Route::get('/profile-update', [UserController::class,'updateProfile']);
    Route::get('/verify-mail/{email}', [UserController::class,'verifyMail']);
    Route::get('/refresh-token', [UserController::class,'refreshToken']);
});
