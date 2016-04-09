<?php

use \Shpasser\GaeSupportL5\Http\Controllers\ArtisanConsoleController;

/**
 * Maintenance routes.
 */
Route::get('artisan',  array('as' => 'artisan',
    'uses' => ArtisanConsoleController::class.'@show'));

Route::post('artisan', array('as' => 'artisan',
    'uses' => ArtisanConsoleController::class.'@execute'));
