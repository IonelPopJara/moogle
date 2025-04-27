<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirect to https://moogle.app
    if (config('app.env') === 'local') {
        return response()->json(['message' => 'Welcome to Moogle!']);
    } else {
        return redirect('https://moogle.app');
    }
});
