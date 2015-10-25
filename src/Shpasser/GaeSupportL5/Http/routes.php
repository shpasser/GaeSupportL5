<?php

use \Shpasser\GaeSupportL5\Http\Controllers\ArtisanConsoleController;

/**
 * Maintenance routes.
 */
get('artisan',  array('as' => 'artisan',
    'uses' => ArtisanConsoleController::class.'@show'));

post('artisan', array('as' => 'artisan',
     'uses' => ArtisanConsoleController::class.'@execute'));
