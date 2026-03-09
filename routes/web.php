<?php

use App\Core\Router;

Router::group(['middleware' => 'csrf'], function () {

    Router::get('/', 'HomeController@index');

    Router::get('/trips', 'TripController@index');
    Router::get('/trips/search', 'TripController@search');
    Router::get('/covoiturages', 'TripController@index');
    Router::get('/trip/{id}', 'TripController@show');

    Router::get('/contact', 'ContactController@show');
    Router::post('/contact', 'ContactController@send');

    Router::get('/terms', 'PageController@terms');
    Router::get('/privacy', 'PageController@privacy');
    Router::get('/legal', 'PageController@legal');

    Router::group(['middleware' => 'guest'], function () {
        Router::get('/login', 'AuthController@showLogin');
        Router::post('/login', 'AuthController@login');
        Router::get('/register', 'AuthController@showRegister');
        Router::post('/register', 'AuthController@register');
    });

    Router::group(['middleware' => 'auth'], function () {
        Router::get('/logout', 'AuthController@logout');

        Router::get('/profile', 'UserController@profile');
        Router::post('/profile/update', 'UserController@update');
        Router::post('/profile/upload-photo', 'UserController@uploadPhoto');
        Router::post('/profile/delete-photo', 'UserController@deletePhoto');

        Router::get('/my-trips', 'TripController@myTrips');
        Router::post('/my-trips', 'TripController@myTrips');

        Router::post('/api/trip/{id}/join', 'Api\\TripApiController@join');
        Router::post('/api/trip/{id}/cancel', 'Api\\TripApiController@cancel');

        Router::post('/api/review', 'Api\\ReviewApiController@submit');
    });

    Router::group(['middleware' => 'auth'], function () {
        Router::get('/driver/dashboard', 'DriverController@dashboard');
        Router::get('/driver/create-trip', 'DriverController@createTrip');
        Router::post('/driver/create-trip', 'DriverController@storeTrip');

        Router::get('/driver/vehicles', 'VehicleController@index');
        Router::post('/driver/vehicles', 'VehicleController@store');
        Router::post('/driver/vehicles/update', 'VehicleController@update');
        Router::post('/driver/vehicles/delete', 'VehicleController@destroy');

        Router::get('/driver/preferences', 'DriverController@preferences');
        Router::post('/driver/preferences', 'DriverController@savePreferences');
    });

    Router::group(['middleware' => 'role:admin'], function () {
        Router::get('/admin', 'AdminController@index');
        Router::get('/admin/trips', 'AdminController@trips');
        Router::post('/admin/suspend-user', 'AdminController@suspendUser');
        Router::post('/admin/activate-user', 'AdminController@activateUser');
        Router::post('/admin/create-employee', 'AdminController@createEmployee');
        Router::post('/admin/mark-message-read', 'AdminController@markMessageRead');
        Router::post('/admin/add-credits', 'AdminController@addCredits');
    });

    Router::group(['middleware' => 'role:employe'], function () {
        Router::get('/moderator', 'ModeratorController@index');
        Router::post('/moderator/approve-review', 'ModeratorController@approveReview');
        Router::post('/moderator/reject-review', 'ModeratorController@rejectReview');
        Router::post('/moderator/resolve-incident', 'ModeratorController@resolveIncident');
        Router::post('/moderator/mark-message-read', 'ModeratorController@markMessageRead');
    });

});
