<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OpenAIController;

Route::post('/openai/generate', OpenAIController::class);


