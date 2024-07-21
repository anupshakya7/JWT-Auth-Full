<?php

use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('mail-verify/{token}', [UserController::class,'mailVerification']);
Route::get('reset-password', [UserController::class,'resetPasswordLoad']);
Route::post('reset-password', [UserController::class,'resetPassword']);
