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

// Workflow process route (must be after apiResource to avoid conflicts)
Route::post('workflows/{id}/process', [WorkflowController::class, 'process']);
Route::post('workflows/{id}/reset', [WorkflowController::class, 'reset']);
