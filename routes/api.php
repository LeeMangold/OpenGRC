<?php

use App\Http\Controllers\API\AuditController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/audits', [AuditController::class, 'index']);
    Route::get('/audits/{audit}', [AuditController::class, 'show']);
    Route::post('/audits', [AuditController::class, 'store']);
    Route::put('/audits/{audit}', [AuditController::class, 'update']);
    Route::delete('/audits/{audit}', [AuditController::class, 'destroy']);

});



