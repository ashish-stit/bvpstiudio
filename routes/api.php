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
Route::middleware(['auth:api','cors'])->get('/user', function (Request $request) {
	return $request->user();
});
Route::post('login', 'API\UserController@login');
Route::post('register', 'API\UserController@register');

Route::group(['middleware' => 'auth:api'], function() {
	Route::post('details', 'API\UserController@details');
});
Route::post('createFolder', 'API\UploadFileController@savefolder');
Route::get('viewfolder', 'API\UploadFileController@viewfolder')->middleware('cors');
Route::get('getfolder', 'API\UploadFileController@getfolder')->middleware('cors');
Route::post('uploadimage', 'API\UploadFileController@uploadimg');
Route::post('uploadvideo', 'API\UploadFileController@uploadvideo');
Route::post('uploadtxtfile', 'API\UploadFileController@uploadtextfile');
Route::post('viewalldata', 'API\UploadFileController@viewalldata');
Route::get('editfolder', 'API\UploadFileController@editfolder');
Route::post('updatefolder', 'API\UploadFileController@updatefolder');
Route::post('deletefolder', 'API\UploadFileController@deletefolder');
Route::post('uploadedfile', 'API\UploadFileController@uploadedfile');
Route::post('imagelist','API\UploadFileController@imagelist');
Route::post('createproject', 'API\UploadFileController_old@savefolder');






