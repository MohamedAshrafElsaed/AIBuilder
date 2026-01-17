<?php

use App\Http\Controllers\UserInputController;
use Illuminate\Support\Facades\Route;

Route::get('/user-inputs/create', [UserInputController::class, 'create'])->name('user-inputs.create');
Route::post('/user-inputs', [UserInputController::class, 'store'])->name('user-inputs.store');
Route::get('/user-inputs/{userInput}', [UserInputController::class, 'show'])->name('user-inputs.show');
