<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ModuleTemplateController;
use App\Http\Controllers\Api\ProjectModuleController as ApiProjectModuleController;
use App\Http\Controllers\Api\ProjectController as ApiProjectController;
use App\Http\Controllers\Api\ProjectStatusController as ApiProjectStatusController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TaskController as ApiTaskController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SequenceConfigController;
use App\Http\Controllers\Api\UserController as ApiUserController;
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

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['api', 'jwt.auth'])->group(function () {
    Route::get('/users', [ApiUserController::class, 'index'])->middleware('role:Admin');
    Route::post('/users', [ApiUserController::class, 'store'])->middleware('role:Admin');
    Route::get('/users/{user}', [ApiUserController::class, 'show'])->middleware('role:Admin');
    Route::patch('/users/{user}', [ApiUserController::class, 'update'])->middleware('role:Admin');
    Route::patch('/users/{user}/activate', [ApiUserController::class, 'activate'])->middleware('role:Admin');
    Route::delete('/users/{user}', [ApiUserController::class, 'destroy'])->middleware('role:Admin');

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('role:Admin,PM');
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->middleware('role:Admin,PM');
    Route::patch('/customers/{customer}/activate', [CustomerController::class, 'activate'])->middleware('role:Admin,PM');

    Route::get('/contacts', [ContactController::class, 'index']);
    Route::post('/contacts', [ContactController::class, 'store'])->middleware('role:Admin,PM');
    Route::get('/contacts/{contact}', [ContactController::class, 'show']);
    Route::patch('/contacts/{contact}', [ContactController::class, 'update'])->middleware('role:Admin,PM');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->middleware('role:Admin,PM');

    Route::get('/config/sequences', [SequenceConfigController::class, 'index'])->middleware('role:Admin');
    Route::post('/config/sequences', [SequenceConfigController::class, 'store'])->middleware('role:Admin');
    Route::patch('/config/sequences/{sequence}', [SequenceConfigController::class, 'update'])->middleware('role:Admin');

    Route::get('/config/project-statuses', [ApiProjectStatusController::class, 'index'])->middleware('role:Admin');
    Route::post('/config/project-statuses', [ApiProjectStatusController::class, 'store'])->middleware('role:Admin');
    Route::patch('/config/project-statuses/{project_status}', [ApiProjectStatusController::class, 'update'])->middleware('role:Admin');
    Route::post('/config/project-statuses/{project_status}/set-default', [ApiProjectStatusController::class, 'setDefault'])->middleware('role:Admin');
    Route::patch('/config/project-statuses/{project_status}/activate', [ApiProjectStatusController::class, 'activate'])->middleware('role:Admin');

    Route::get('/config/module-templates', [ModuleTemplateController::class, 'index'])->middleware('role:Admin');
    Route::post('/config/module-templates', [ModuleTemplateController::class, 'store'])->middleware('role:Admin');
    Route::patch('/config/module-templates/{module_template}', [ModuleTemplateController::class, 'update'])->middleware('role:Admin');
    Route::patch('/config/module-templates/{module_template}/activate', [ModuleTemplateController::class, 'activate'])->middleware('role:Admin');

    Route::get('/audit', [AuditLogController::class, 'index'])->middleware('role:Admin,PM');

    Route::get('/projects', [ApiProjectController::class, 'index'])->middleware('role:Admin,PM,Developer,Viewer');
    Route::post('/projects', [ApiProjectController::class, 'store'])->middleware('role:Admin,PM');
    Route::get('/projects/{project}', [ApiProjectController::class, 'show'])->middleware('role:Admin,PM,Developer,Viewer');
    Route::patch('/projects/{project}', [ApiProjectController::class, 'update'])->middleware('role:Admin,PM');
    Route::patch('/projects/{project}/activate', [ApiProjectController::class, 'activate'])->middleware('role:Admin,PM');
    Route::patch('/projects/{project}/status', [ApiProjectController::class, 'changeStatus'])->middleware('role:Admin,PM');
    Route::delete('/projects/{project}', [ApiProjectController::class, 'destroy'])->middleware('role:Admin');

    Route::get('/projects/{project}/team', [ApiProjectController::class, 'team'])->middleware('role:Admin,PM,Developer,Viewer');
    Route::post('/projects/{project}/team', [ApiProjectController::class, 'upsertTeam'])->middleware('role:Admin,PM');
    Route::delete('/projects/{project}/team/{user_id}', [ApiProjectController::class, 'removeTeamMember'])->middleware('role:Admin,PM');

    Route::post('/projects/{project}/modules/init', [ApiProjectModuleController::class, 'init'])->middleware('role:Admin,PM');
    Route::get('/projects/{project}/modules', [ApiProjectModuleController::class, 'index'])->middleware('role:Admin,PM,Developer,Viewer');
    Route::post('/projects/{project}/modules', [ApiProjectModuleController::class, 'store'])->middleware('role:Admin,PM');
    Route::patch('/modules/{module}', [ApiProjectModuleController::class, 'update'])->middleware('role:Admin,PM');
    Route::patch('/modules/{module}/activate', [ApiProjectModuleController::class, 'activate'])->middleware('role:Admin,PM');

    Route::get('/tasks', [ApiTaskController::class, 'index'])->middleware('role:Admin,PM,Developer,Viewer');
    Route::post('/projects/{project}/modules/{module}/tasks', [ApiTaskController::class, 'store'])->middleware('role:Admin,PM');
    Route::get('/tasks/{task}', [ApiTaskController::class, 'show'])->middleware('role:Admin,PM,Developer,Viewer');
    Route::patch('/tasks/{task}', [ApiTaskController::class, 'update'])->middleware('role:Admin,PM,Developer');
    Route::delete('/tasks/{task}', [ApiTaskController::class, 'destroy'])->middleware('role:Admin,PM');

    Route::get('/dashboards/product', [DashboardController::class, 'product'])->middleware('role:Admin,PM,Developer,Viewer');
    Route::get('/dashboards/tasks', [DashboardController::class, 'tasks'])->middleware('role:Admin,PM,Developer,Viewer');

    Route::get('/search', SearchController::class);
});

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
