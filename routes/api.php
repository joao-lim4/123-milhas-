<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FlightController;




Route::group([
    'prefix'  => 'v1',
], function () {

    Route::get('/flights',[ FlightController::class, 'getFlightApi']);

});
