<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

// First Page (Form)
Route::get('/{serial}/{fmId}/', array(
        'as' => 'form',
        'uses' => 'PageController@index'
    ))
    ->where('serial', '[A-Za-z\-\d+]+')
    ->where('fmId', '[\d+]+');

Route::get('/http', function(){
    echo route('form', array('serial' => 123, 'fmId' => 345));
});

Route::resource('clients', 'ClientsController');

App::missing(function($exception){
    return View::make('error');
});

