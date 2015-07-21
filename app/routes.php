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


// ===============================================
// LOGIN SECTION =================================
// ===============================================
// show the login page
Route::get('login', array(
    'uses' => 'PageController@showLogin'
));

// process the login
Route::post('login', array(
    'uses' => 'PageController@doLogin'
));

Route::get('logout', array(
    'uses' => 'PageController@doLogout'
));

// ===============================================
// ADMIN SECTION =================================
// ===============================================
Route::group(array('prefix' => 'admin', 'before' => 'auth'), function()
{
    Route::get('/', function(){
        return View::make('admin.dashboard');
    });

    Route::resource('clients', 'ClientsController');

    Route::resource('users', 'UsersController');
});

// ===============================================
// DATA SECTION ==================================
// ===============================================
Route::group(array('prefix' => 'data'), function()
{
    Route::get('{serial}/jobs/external/{format?}', 'DataController@externalJobList')
        ->where('serial', '[A-Za-z\-\d+]+');

    Route::get('{serial}/jobs/{sortDirection}/{type?}', 'DataController@jobListAll')
        ->where('serial', '[A-Za-z\-\d+]+');


    Route::get('/{serial}/job-detail/{jobId}', array(
        'uses' => 'DataController@jobDetail'
    ))
        ->where('serial', '[A-Za-z\-\d+]+')
        ->where('jobId', '[\d]+');
});

// First Page (Form)
Route::any('/{serial}/{fmId}', array(
        'as' => 'form',
        'uses' => 'PageController@index'
    ))
    ->where('serial', '[A-Za-z\-\d+]+')
    ->where('fmId', '[\d+]+');

// Systemcheck Xing
Route::any('/systemcheck/{serial}', array(
    'as' => 'form',
    'uses' => 'PageController@systemCheck'
))
    ->where('serial', '[A-Za-z\-\d+]+')
    ->where('fmId', '[\d+]+');

/*Route::group(array('prefix' => 'xing'), function(){
    Route::get('/', 'XingController@showIndex');

    Route::get('/status', 'XingController@jsonXingLoggedIn');

    Route::get('/statusRaw', 'XingController@statusRaw');

    Route::get('/login', 'XingController@showXingLogin');

    Route::get('/data/{serial}/{fmId}', array(
        'uses' => 'XingController@doSearch'
    ))
        ->where('serial', '[A-Za-z\-\d+]+')
        ->where('fmId', '[\d]+');

});*/



Route::get('/debug/fm', function(){
    include_once(base_path().'/app/libraries/filemaker-12/FileMaker.php');
    $fm = new FileMaker('K5_RO','http://host1.kon5.net/','web_pape','xs4web_pape');

    $findCommand = $fm->newFindAnyCommand('Projektliste_Web');

    $result = $findCommand->execute();
    print_r($result);
});

Route::get('debug/test', function(){

    $curlUrl = 'http://www.xing.de/places?mhp_id='.$mhpId;

    $curlHandle = curl_init($curlUrl);
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

    $requestData = curl_exec($curlHandle);

    curl_close($curlHandle);

});


App::missing(function($exception) {
    // shows an error page (app/views/error.blade.php)
    // returns a page not found error
    return Response::view('error', array(), 404);
});


