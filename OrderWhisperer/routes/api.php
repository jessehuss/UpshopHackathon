<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpenAIController;
use App\Http\Controllers\Api\OpenAIStreamController;

Route::post('/openai/generate', OpenAIController::class);
Route::post('/openai/generate/stream', OpenAIStreamController::class);


