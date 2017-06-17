<?php

use Illuminate\Http\Request;

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
Route::group(['prefix' => 'auth'], function () {
    Route::post('login','Auth\LoginController@authenticate');
    Route::get('logout','Auth\LoginController@logout');
    Route::get('check','Auth\LoginController@check');
});
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
