<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return file_get_contents(__DIR__ . '/../public/landing.html');
});

Route::get('/dashboard', function () {
    return view('welcome');
});
