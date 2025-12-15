<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\MeetingScheduleController;
use App\Http\Controllers\MomController;
use App\Http\Controllers\HrUpdateController;
use App\Http\Controllers\DriveAuthController;
use App\Http\Controllers\DriveUiController;
use App\Http\Controllers\ModulePagesController;

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
