<?php

use App\Http\Controllers\ResourceMetaController;
use App\Http\Controllers\WorkNodeController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

// Resource Meta RESTful routes
Route::apiResource('resource-metas', ResourceMetaController::class);

// Work Node RESTful routes
Route::apiResource('work-nodes', WorkNodeController::class);

// Workflow RESTful routes
Route::apiResource('workflows', WorkflowController::class);
