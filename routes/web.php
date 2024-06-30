<?php

use App\Http\Controllers\BotManController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::match(['get', 'post'], 'botman', [BotManController::class, 'handle']);
Route::get('users', function () {
    return response()->json(['users' => User::all()]);
});
