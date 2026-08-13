<?php

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

/*
*
* Frontend Routes
*
* --------------------------------------------------------------------
*/
Route::group(['namespace' => '\Modules\Order\Http\Controllers\Frontend', 'as' => 'frontend.', 'middleware' => 'web', 'prefix' => ''], function () {

    /*
     *
     *  Frontend Orders Routes
     *
     * ---------------------------------------------------------------------
     */
    $module_name = 'orders';
    $controller_name = 'OrdersController';
    Route::get("$module_name", ['as' => "$module_name.index", 'uses' => "$controller_name@index"]);
    Route::get("$module_name/{id}/{slug?}", ['as' => "$module_name.show", 'uses' => "$controller_name@show"]);
    Route::post("$module_name/checkCar", ['as' => "$module_name.checkCar", 'uses' => "$controller_name@checkCar"]);
});

/*
*
* Backend Routes
*
* --------------------------------------------------------------------
*/
Route::group(['namespace' => '\Modules\Order\Http\Controllers\Backend', 'as' => 'backend.', 'middleware' => ['web', 'auth', 'can:view_backend'], 'prefix' => 'admin'], function () {
    /*
    * These routes need view-backend permission
    * (good if you want to allow more than one group in the backend,
    * then limit the backend features by different roles or permissions)
    *
    * Note: Administrator has all permissions so you do not have to specify the administrator role everywhere.
    */

    /*
     *
     *  Backend Orders Routes
     *
     * ---------------------------------------------------------------------
     */
    $module_name = 'orders';
    $controller_name = 'OrdersController';
    Route::match(['patch', 'post'], "$module_name/renew", ['as' => "$module_name.renew", 'uses' => "$controller_name@renew"]);
    Route::post("$module_name/renewComplete", ['as' => "$module_name.renewComplete", 'uses' => "$controller_name@renewComplete"]);
    Route::get("$module_name/index_list", ['as' => "$module_name.index_list", 'uses' => "$controller_name@index_list"]);
    Route::get("$module_name/index_data", ['as' => "$module_name.index_data", 'uses' => "$controller_name@index_data"]);
    Route::post("$module_name/renewNotification", ['as' => "$module_name.send.renew.notification", 'uses' => "$controller_name@renewNotification"]);
    Route::get("$module_name/trashed", ['as' => "$module_name.trashed", 'uses' => "$controller_name@trashed"]);
    Route::patch("$module_name/trashed/{id}", ['as' => "$module_name.restore", 'uses' => "$controller_name@restore"]);
    Route::get("$module_name/topUp/{id}", ['as' => "$module_name.topUp", 'uses' => "$controller_name@topUp"]);
    Route::match(['patch', 'post'], "$module_name/addTopUp", ['as' => "$module_name.addTopUp", 'uses' => "$controller_name@addTopUp"]);
    Route::match(['patch', 'post'], "$module_name/addTopUpComplete", ['as' => "$module_name.addTopUpComplete", 'uses' => "$controller_name@addTopUpComplete"]);
    Route::resource("$module_name", "$controller_name");
});

Route::group(['namespace' => '\Modules\Order\Http\Controllers\Backend', 'as' => 'backend.', 'middleware' => ['web',], 'prefix' => 'admin'], function () {
    $module_name = 'orders';
    $controller_name = 'OrdersController';
    Route::post("$module_name/loginFreerenewComplete", ['as' => "$module_name.loginFreerenewComplete", 'uses' => "$controller_name@loginFreerenewComplete"]);
});
