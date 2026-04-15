<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['app' => 'RoutineApp API', 'status' => 'running'];
});
