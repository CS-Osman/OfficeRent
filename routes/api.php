<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Tags
Route::get('/tags', [App\Http\Controllers\TagController::class , '__invoke']);

//Offices
Route::get('/offices', [App\Http\Controllers\OfficeController::class , 'index']);
Route::get('/offices/{office}', [App\Http\Controllers\OfficeController::class , 'show']);
