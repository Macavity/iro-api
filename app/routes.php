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
Route::any('/{serial}/{fmId}/', array(
        'as' => 'form',
        'uses' => 'PageController@index'
    ))
    ->where('serial', '[A-Za-z\-\d+]+')
    ->where('fmId', '[\d+]+');

Route::get('/debug/fm', function(){
    include_once(base_path().'/app/libraries/filemaker-12/FileMaker.php');
    $fm = new FileMaker('K5_RO','http://host1.kon5.net/','web_pape','xs4web_pape');

    $findCommand = $fm->newFindAnyCommand('Projektliste_Web');

    $result = $findCommand->execute();
    print_r($result);
});

// ===============================================
// LOGIN SECTION =================================
// ===============================================
// show the login page
Route::get('login', function()
{
    // show the login page (app/views/login.blade.php)
    return View::make('login');
});

// process the login
Route::post('login', function()
{
    // validate
    // process login
    // if successful, redirect
    return Redirect::intended();
});

// ===============================================
// ADMIN SECTION =================================
// ===============================================
Route::group(array('prefix' => 'admin', 'before' => 'auth'), function()
{
    // main page for the admin section (app/views/admin/dashboard.blade.php)
    Route::get('/', function()
    {
        return View::make('admin.dashboard');
    });

    // subpage for the posts found at /admin/posts (app/views/admin/posts.blade.php)
    Route::get('posts', function()
    {
        return View::make('admin.posts');
    });

    // subpage to create a post found at /admin/posts/create (app/views/admin/posts-create.blade.php)
    Route::get('posts/create', function()
    {
        return View::make('admin.posts-create');
    });
    Route::resource('clients', 'ClientsController');
});


App::missing(function($exception) {
    // shows an error page (app/views/error.blade.php)
    // returns a page not found error
    return Response::view('error', array(), 404);
});

