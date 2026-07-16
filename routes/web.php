<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::user.dashboard');
Route::livewire('/login', 'pages::admin.login');
Route::livewire('/admin/dashboard', 'pages::admin.dashboard');