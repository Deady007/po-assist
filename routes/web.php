<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\MeetingScheduleController;
use App\Http\Controllers\MomController;
use App\Http\Controllers\HrUpdateController;
use App\Http\Controllers\DriveAuthController;
use App\Http\Controllers\DriveUiController;
use App\Http\Controllers\ModulePagesController;
use App\Http\Controllers\CustomerPageController;
use App\Http\Controllers\ContactPageController;
use App\Http\Controllers\SearchPageController;
use App\Modules\UserManagement\Http\Controllers\UserController as AdminUserController;
use App\Modules\ClientManagement\Http\Controllers\ClientController as AdminClientController;
use App\Modules\ProjectManagement\Http\Controllers\ProjectController as AdminProjectController;
use App\Modules\ProjectManagement\Http\Controllers\ProjectModuleController;
use App\Modules\ProjectManagement\Http\Controllers\TaskController;
use App\Modules\Configuration\Http\Controllers\ProjectStatusController;
use App\Modules\Configuration\Http\Controllers\SequenceConfigController;
use App\Modules\Configuration\Http\Controllers\EmailTemplateController;
use App\Modules\ImportExport\Http\Controllers\ImportExportController;

Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.submit')->middleware('guest');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/drive/connect', [DriveUiController::class, 'connect'])->name('drive.connect');
    Route::get('/drive/oauth/start', [DriveAuthController::class, 'start'])->name('drive.oauth.start');
    Route::get('/drive/oauth/callback', [DriveAuthController::class, 'callback'])->name('drive.oauth.callback');
    Route::get('/projects/{project}/drive', [DriveUiController::class, 'project'])->name('projects.drive');
    Route::get('/developers', [ModulePagesController::class, 'developers'])->name('modules.developers');
    Route::get('/projects/{project}/requirements', [ModulePagesController::class, 'requirements'])->name('modules.requirements');
    Route::get('/projects/{project}/data-items', [ModulePagesController::class, 'dataItems'])->name('modules.data_items');
    Route::get('/projects/{project}/master-data', [ModulePagesController::class, 'masterData'])->name('modules.master_data');
    Route::get('/projects/{project}/assignments', [ModulePagesController::class, 'assignments'])->name('modules.assignments');
    Route::get('/projects/{project}/bugs', [ModulePagesController::class, 'bugs'])->name('modules.bugs');
    Route::get('/projects/{project}/testing', [ModulePagesController::class, 'testing'])->name('modules.testing');
    Route::get('/projects/{project}/tokens', [ModulePagesController::class, 'tokens'])->name('modules.tokens');
    Route::get('/projects/{project}/validation-reports', [ModulePagesController::class, 'validationReports'])->name('modules.validation_reports');

    Route::get('/emails/product-update', [EmailController::class, 'productUpdateForm'])->name('emails.product.form');
    Route::post('/emails/product-update', [EmailController::class, 'productUpdateGenerate'])->name('emails.product.generate');

    Route::get('/emails/meeting-schedule', [MeetingScheduleController::class, 'create'])->name('emails.meeting.form');
    Route::post('/emails/meeting-schedule', [MeetingScheduleController::class, 'store'])->name('emails.meeting.generate');

    Route::get('/emails/mom/draft', [MomController::class, 'draftForm'])->name('emails.mom.draft.form');
    Route::post('/emails/mom/draft', [MomController::class, 'draftGenerate'])->name('emails.mom.draft.generate');
    Route::get('/emails/mom/{draft}/refine', [MomController::class, 'refineForm'])->name('emails.mom.refine.form');
    Route::post('/emails/mom/{draft}/refine', [MomController::class, 'refineGenerate'])->name('emails.mom.refine.generate');
    Route::get('/emails/mom/refined/{refined}/final', [MomController::class, 'finalForm'])->name('emails.mom.final.form');
    Route::post('/emails/mom/refined/{refined}/final', [MomController::class, 'finalGenerate'])->name('emails.mom.final.generate');

    Route::get('/emails/hr-update', [HrUpdateController::class, 'create'])->name('emails.hr.form');
    Route::post('/emails/hr-update', [HrUpdateController::class, 'store'])->name('emails.hr.generate');

    Route::get('/history', [EmailController::class, 'history'])->name('history');
    Route::get('/history/{id}', [EmailController::class, 'historyShow'])->name('history.show');
    Route::get('/search', SearchPageController::class)->name('search');

    Route::prefix('dashboard')->group(function () {
        Route::get('/product', [DashboardController::class, 'product'])->name('dashboard.product');
        Route::get('/tasks', [DashboardController::class, 'tasks'])->name('dashboard.tasks');
        Route::get('/workload', [DashboardController::class, 'workload'])->name('dashboard.workload');
    });

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('customers', [CustomerPageController::class, 'index'])->name('customers.index');
        Route::get('customers/new', [CustomerPageController::class, 'create'])->name('customers.create')->middleware('role:Admin,PM');
        Route::post('customers', [CustomerPageController::class, 'store'])->name('customers.store')->middleware('role:Admin,PM');
        Route::get('customers/{customer}', [CustomerPageController::class, 'show'])->name('customers.show');
        Route::patch('customers/{customer}', [CustomerPageController::class, 'update'])->name('customers.update')->middleware('role:Admin,PM');
        Route::patch('customers/{customer}/activate', [CustomerPageController::class, 'activate'])->name('customers.activate')->middleware('role:Admin,PM');

        Route::get('contacts', [ContactPageController::class, 'index'])->name('contacts.index');
        Route::post('contacts', [ContactPageController::class, 'store'])->name('contacts.store')->middleware('role:Admin,PM');
        Route::get('contacts/{contact}/edit', [ContactPageController::class, 'edit'])->name('contacts.edit')->middleware('role:Admin,PM');
        Route::patch('contacts/{contact}', [ContactPageController::class, 'update'])->name('contacts.update')->middleware('role:Admin,PM');
        Route::delete('contacts/{contact}', [ContactPageController::class, 'destroy'])->name('contacts.destroy')->middleware('role:Admin,PM');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', AdminUserController::class)->except(['show', 'create'])->middleware('role:Admin');
        Route::resource('clients', AdminClientController::class)->except(['show', 'create'])->middleware('role:Admin,PM');

        Route::get('projects', [AdminProjectController::class, 'index'])->name('projects.index')->middleware('role:Admin,PM,Developer,Viewer');
        Route::get('projects/{project}', [AdminProjectController::class, 'show'])->name('projects.show')->middleware('role:Admin,PM,Developer,Viewer');
        Route::get('projects/{project}/workflow', [AdminProjectController::class, 'workflow'])->name('projects.workflow')->middleware('role:Admin,PM,Developer,Viewer');
        Route::get('projects/{project}/tasks', [AdminProjectController::class, 'tasksView'])->name('projects.tasks')->middleware('role:Admin,PM,Developer,Viewer');
        Route::get('projects/{project}/emails', [AdminProjectController::class, 'emails'])->name('projects.emails')->middleware('role:Admin,PM,Developer,Viewer');
        Route::resource('projects', AdminProjectController::class)->except(['index', 'show'])->middleware('role:Admin,PM');
        Route::post('projects/{project}/team', [AdminProjectController::class, 'addTeamMember'])->name('projects.team.store')->middleware('role:Admin,PM');
        Route::delete('projects/{project}/team/{team}', [AdminProjectController::class, 'removeTeamMember'])->name('projects.team.destroy')->middleware('role:Admin,PM');

        Route::post('projects/{project}/modules/init', [ProjectModuleController::class, 'init'])->name('projects.modules.init')->middleware('role:Admin,PM');
        Route::post('projects/{project}/modules', [ProjectModuleController::class, 'store'])->name('projects.modules.store')->middleware('role:Admin,PM');
        Route::put('projects/{project}/modules/{module}', [ProjectModuleController::class, 'update'])->name('projects.modules.update')->middleware('role:Admin,PM');
        Route::delete('projects/{project}/modules/{module}', [ProjectModuleController::class, 'destroy'])->name('projects.modules.destroy')->middleware('role:Admin,PM');

        Route::post('projects/{project}/modules/{module}/tasks', [TaskController::class, 'store'])->name('projects.modules.tasks.store')->middleware('role:Admin,PM');
        Route::put('projects/{project}/modules/{module}/tasks/{task}', [TaskController::class, 'update'])->name('projects.modules.tasks.update')->middleware('role:Admin,PM,Developer');
        Route::delete('projects/{project}/modules/{module}/tasks/{task}', [TaskController::class, 'destroy'])->name('projects.modules.tasks.destroy')->middleware('role:Admin,PM');

        Route::get('config/statuses', [ProjectStatusController::class, 'index'])->name('config.statuses.index')->middleware('role:Admin');
        Route::post('config/statuses', [ProjectStatusController::class, 'store'])->name('config.statuses.store')->middleware('role:Admin');
        Route::put('config/statuses/{status}', [ProjectStatusController::class, 'update'])->name('config.statuses.update')->middleware('role:Admin');
        Route::delete('config/statuses/{status}', [ProjectStatusController::class, 'destroy'])->name('config.statuses.destroy')->middleware('role:Admin');

        Route::get('config/sequences', [SequenceConfigController::class, 'index'])->name('config.sequences.index')->middleware('role:Admin');
        Route::post('config/sequences', [SequenceConfigController::class, 'store'])->name('config.sequences.store')->middleware('role:Admin');
        Route::put('config/sequences/{sequence}', [SequenceConfigController::class, 'update'])->name('config.sequences.update')->middleware('role:Admin');

        Route::get('config/email-templates', [EmailTemplateController::class, 'index'])->name('config.email_templates.index')->middleware('role:Admin');
        Route::post('config/email-templates', [EmailTemplateController::class, 'store'])->name('config.email_templates.store')->middleware('role:Admin');
        Route::put('config/email-templates/{template}', [EmailTemplateController::class, 'update'])->name('config.email_templates.update')->middleware('role:Admin');
        Route::delete('config/email-templates/{template}', [EmailTemplateController::class, 'destroy'])->name('config.email_templates.destroy')->middleware('role:Admin');

        Route::get('import-export', [ImportExportController::class, 'index'])->name('import-export.index')->middleware('role:Admin');
        Route::post('import-export/export', [ImportExportController::class, 'export'])->name('import-export.export')->middleware('role:Admin');
        Route::post('import-export/import', [ImportExportController::class, 'import'])->name('import-export.import')->middleware('role:Admin');
    });
});
