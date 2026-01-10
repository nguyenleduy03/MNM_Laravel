<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'Agent For Edu - Laravel API',
        'version' => '1.0.0',
        'documentation' => '/api/health',
        'spring_boot_equivalent' => 'http://localhost:8080',
    ]);
});
