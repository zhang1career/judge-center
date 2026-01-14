<?php

use App\Http\Controllers\ResourceMetaController;
use Illuminate\Support\Facades\Route;

// Resource Meta RESTful routes
Route::apiResource('resource-metas', ResourceMetaController::class);
