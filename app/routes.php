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
        'uses' => 'DataController@jobDetailFallback'
    ))
        ->where('serial', '[A-Za-z\-\d+]+')
        ->where('jobId', '[\d]+');

    Route::get('/{serial}/job-detail-check/{jobId}', array(
        'uses' => 'DataController@jobDetailCheck'
    ))
        ->where('serial', '[A-Za-z\-\d+]+')
        ->where('jobId', '[\d]+');

});

// ===============================================
// Search Engine (Premium)========================
// ===============================================
Route::group(array('prefix' => 'search'), function()
{
    // Import
    Route::get('{serial}/import/{type?}', 'AlgoliaController@import')
        ->where('serial', '[A-Za-z\-\d+]+');

    // Joblist
    Route::get('{serial}/joblist', 'AlgoliaController@jobListAll')
        ->where('serial', '[A-Za-z\-\d+]+');

    // Refresh Cache
    Route::get('{serial}/check-cache/jobs/{type?}', 'AlgoliaController@checkCache')
        ->where('serial', '[A-Za-z\-\d+]+');

    // Single Refresh
    Route::get('{serial}/check-cache/single/{jobId}', 'AlgoliaController@singleRefresh')
        ->where('serial', '[A-Za-z\-\d+]+')
        ->where('jobId', '[\d]+');
    
    // Clean index and full refresh
    Route::get('{serial}/clean-import', 'AlgoliaController@cleanImport')
        ->where('serial', '[A-Za-z\-\d+]+');
});


// First Page (Form)
Route::any('/{serial}/{fmId}', array(
        'as' => 'form',
        'uses' => 'PageController@index'
    ))
    ->where('serial', '[A-Za-z\-\d+]+')
    ->where('fmId', '[\d+]+');

// Systemcheck Xing
Route::any('/{serial}/systemcheck', array(
    'uses' => 'PageController@systemCheck'
))
    ->where('serial', '[A-Za-z\-\d+]+');

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

    $fm = new FileMaker(Config::get('filemaker.db'), Config::get('filemaker.host'), Config::get('filemaker.username'), Config::get('filemaker.password'));

    $findCommand = $fm->newFindAnyCommand(Config::get('filemaker.project_list'));

    $result = $findCommand->execute();

    $resultString = print_r($result, true);

    $resultString = str_replace(Config::get('filemaker.username'), "****", $resultString);
    $resultString = str_replace(Config::get('filemaker.password'), "****", $resultString);

    echo "\n<br>getFoundSetCount:".$result->getFoundSetCount();
    echo "\n<br>getFetchCount:".$result->getFetchCount();

});

Route::get('debug/test', function(){

    echo "<br>Environment: ".App::environment();

    $curlUrl = 'http://www.xing.de/';

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


