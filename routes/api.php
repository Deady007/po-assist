<?php

use App\Http\Controllers\BugsController;
use App\Http\Controllers\DataItemsController;
use App\Http\Controllers\DevelopersController;
use App\Http\Controllers\DriveFilesController;
use App\Http\Controllers\MasterDataChangesController;
use App\Http\Controllers\RequirementAssignmentsController;
use App\Http\Controllers\RequirementsController;
use App\Http\Controllers\RfpDocumentsController;
use App\Http\Controllers\TestCasesController;
use App\Http\Controllers\TestersController;
use App\Http\Controllers\TestRunsController;
use App\Http\Controllers\TokenRequestsController;
use App\Http\Controllers\TokenWalletController;
use App\Http\Controllers\ValidationReportsController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\WarningsController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/developers', [DevelopersController::class, 'index']);
    Route::post('/developers', [DevelopersController::class, 'store']);

    Route::get('/testers', [TestersController::class, 'index']);
    Route::post('/testers', [TestersController::class, 'store']);

    Route::prefix('projects/{projectId}')->group(function () {
        Route::get('/warnings', [WarningsController::class, 'projectWarnings']);

        // Drive integration
        Route::prefix('drive')->group(function () {
            Route::post('/provision', [DriveFilesController::class, 'provision']);
            Route::post('/upload', [DriveFilesController::class, 'upload']);
            Route::post('/link', [DriveFilesController::class, 'link']);
            Route::get('/files', [DriveFilesController::class, 'list']);
        });

        // Requirements + RFP
        Route::get('/requirements', [RequirementsController::class, 'index']);
        Route::post('/requirements', [RequirementsController::class, 'store']);
        Route::get('/requirements/{id}', [RequirementsController::class, 'show']);
        Route::put('/requirements/{id}', [RequirementsController::class, 'update']);
        Route::delete('/requirements/{id}', [RequirementsController::class, 'destroy']);
        Route::post('/rfp-documents/upload', [RfpDocumentsController::class, 'upload']);
        Route::post('/rfp-documents/link', [RfpDocumentsController::class, 'link']);

        // Data items
        Route::get('/data-items', [DataItemsController::class, 'index']);
        Route::post('/data-items', [DataItemsController::class, 'store']);
        Route::put('/data-items/{id}', [DataItemsController::class, 'update']);
        Route::delete('/data-items/{id}', [DataItemsController::class, 'destroy']);
        Route::post('/data-items/{id}/upload', [DataItemsController::class, 'upload']);

        // Master data changes
        Route::get('/master-data-changes', [MasterDataChangesController::class, 'index']);
        Route::post('/master-data-changes', [MasterDataChangesController::class, 'store']);
        Route::put('/master-data-changes/{id}', [MasterDataChangesController::class, 'update']);
        Route::delete('/master-data-changes/{id}', [MasterDataChangesController::class, 'destroy']);

        // Assignments + bugs
        Route::get('/assignments', [RequirementAssignmentsController::class, 'index']);
        Route::post('/assignments', [RequirementAssignmentsController::class, 'store']);
        Route::put('/assignments/{id}', [RequirementAssignmentsController::class, 'update']);
        Route::delete('/assignments/{id}', [RequirementAssignmentsController::class, 'destroy']);

        Route::get('/bugs', [BugsController::class, 'index']);
        Route::post('/bugs', [BugsController::class, 'store']);
        Route::put('/bugs/{id}', [BugsController::class, 'update']);
        Route::delete('/bugs/{id}', [BugsController::class, 'destroy']);

        // Testing
        Route::get('/test-cases', [TestCasesController::class, 'index']);
        Route::post('/test-cases', [TestCasesController::class, 'store']);
        Route::put('/test-cases/{id}', [TestCasesController::class, 'update']);
        Route::delete('/test-cases/{id}', [TestCasesController::class, 'destroy']);

        Route::get('/test-runs', [TestRunsController::class, 'index']);
        Route::post('/test-runs', [TestRunsController::class, 'store']);
        Route::get('/test-runs/{runId}', [TestRunsController::class, 'show']);
        Route::post('/test-runs/{runId}/results', [TestRunsController::class, 'storeResults']);
        Route::get('/testing/coverage', [TestRunsController::class, 'coverage']);

        // Delivery + tokens
        Route::get('/delivery', [DeliveryController::class, 'show']);
        Route::post('/delivery', [DeliveryController::class, 'store']);

        Route::get('/token-wallet', [TokenWalletController::class, 'show']);
        Route::put('/token-wallet', [TokenWalletController::class, 'update']);

        Route::get('/token-requests', [TokenRequestsController::class, 'index']);
        Route::post('/token-requests', [TokenRequestsController::class, 'store']);
        Route::put('/token-requests/{id}', [TokenRequestsController::class, 'update']);
        Route::delete('/token-requests/{id}', [TokenRequestsController::class, 'destroy']);
        Route::post('/token-requests/{id}/transition', [TokenRequestsController::class, 'transition']);

        // Validation reports
        Route::get('/validation-reports', [ValidationReportsController::class, 'index']);
        Route::post('/validation-reports/generate', [ValidationReportsController::class, 'generate']);
        Route::post('/validation-reports/{reportId}/upload-to-drive', [ValidationReportsController::class, 'uploadToDrive']);
    });
});
