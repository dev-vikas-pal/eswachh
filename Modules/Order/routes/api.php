<?php

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

Route::middleware('auth:api')->get('/orders', function (Request $request) {
    return $request->user();
});

Route::group(['namespace' => '\Modules\Order\Http\Controllers\Frontend', 'as' => 'frontend.', 'prefix' => ''], function () {
    $module_name = 'orders';
    $controller_name = 'OrdersController';
    Route::get("$module_name", ['as' => "$module_name.index", 'uses' => "$controller_name@index"]);
    Route::get("$module_name/{id}/{slug?}", ['as' => "$module_name.show", 'uses' => "$controller_name@show"]);
    Route::post("$module_name/price", ['as' => "$module_name.price", 'uses' => "$controller_name@getPrice"]);
    Route::post("$module_name/location", ['as' => "$module_name.location", 'uses' => "$controller_name@getLocationInfos"]);
    Route::post("$module_name/addToCart", ['as' => "$module_name.addToCart", 'uses' => "$controller_name@addToCart"]);
});

Route::group(['namespace' => '\Modules\Order\Http\Controllers\Backend', 'as' => 'backend.', 'middleware' => ['web', 'auth', 'can:view_backend'], 'prefix' => 'admin'], function () {
    $module_name = 'orders';
    $controller_name = 'OrdersController';
    Route::post("$module_name/renew", ['as' => "$module_name.renew", 'uses' => "$controller_name@renew"]);
});
Route::group(['namespace' => '\Modules\Order\Http\Controllers\Backend', 'as' => 'backend.', 'middleware' => ['web'], 'prefix' => 'admin'], function () {
    $module_name = 'orders';
    $controller_name = 'OrdersController';
    Route::post("$module_name/renewLoginFree", ['as' => "$module_name.renewLoginFree", 'uses' => "$controller_name@renewLoginFree"]);
});