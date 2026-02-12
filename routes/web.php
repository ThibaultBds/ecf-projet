<?php

/**
 * Définition de toutes les routes de l'application EcoRide
 */

// -------------------------------------------------------
// Routes publiques
// -------------------------------------------------------

Router::get('/', 'HomeController@index');

// Trajets (publics)
Router::get('/trips', 'TripController@index');
Router::get('/covoiturages', 'TripController@index');
Router::get('/trip/{id}', 'TripController@show');

// Contact
Router::get('/contact', 'ContactController@show');
Router::post('/contact', 'ContactController@send');

// Pages statiques
Router::get('/terms', 'PageController@terms');
Router::get('/privacy', 'PageController@privacy');
Router::get('/legal', 'PageController@legal');

// -------------------------------------------------------
// Routes invité (redirige si déjà connecté)
// -------------------------------------------------------

Router::group(['middleware' => 'guest'], function () {
    Router::get('/login', 'AuthController@showLogin');
    Router::post('/login', 'AuthController@login');
    Router::get('/register', 'AuthController@showRegister');
    Router::post('/register', 'AuthController@register');
});

// -------------------------------------------------------
// Routes authentifiées
// -------------------------------------------------------

Router::group(['middleware' => 'auth'], function () {
    // Logout
    Router::get('/logout', 'AuthController@logout');

    // Profil
    Router::get('/profile', 'UserController@profile');
    Router::post('/profile/update', 'UserController@update');

    // Mes trajets
    Router::get('/my-trips', 'TripController@myTrips');
    Router::post('/my-trips/cancel-participation', 'TripController@cancelParticipation');
    Router::post('/my-trips/update-status', 'TripController@updateStatus');

    // API - Participation
    Router::post('/api/trip/{id}/join', 'Api\\TripApiController@join');
    Router::post('/api/trip/{id}/cancel', 'Api\\TripApiController@cancel');

    // API - Avis
    Router::post('/api/review', 'Api\\ReviewApiController@submit');
});

// -------------------------------------------------------
// Routes chauffeur (authentifié)
// -------------------------------------------------------

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

// -------------------------------------------------------
// Routes admin
// -------------------------------------------------------

Router::group(['middleware' => 'role:Administrateur'], function () {
    Router::get('/admin', 'AdminController@index');
    Router::post('/admin/suspend-user', 'AdminController@suspendUser');
    Router::post('/admin/activate-user', 'AdminController@activateUser');
    Router::post('/admin/create-employee', 'AdminController@createEmployee');
});

// -------------------------------------------------------
// Routes modérateur
// -------------------------------------------------------

Router::group(['middleware' => 'role:Moderateur'], function () {
    Router::get('/moderator', 'ModeratorController@index');
});
