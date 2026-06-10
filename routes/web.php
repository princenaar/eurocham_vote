<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ElectionController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

/*
| Public
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
| QR-gated voter flow (Phase 3) — every rule enforced server-side in VoteController.
*/
Route::controller(VoteController::class)->group(function () {
    Route::get('/vote', 'start')->name('vote.start');
    Route::post('/vote', 'identify')->name('vote.identify');
    Route::get('/vote/bulletin', 'ballot')->name('vote.ballot');
    Route::post('/vote/verification', 'review')->name('vote.review');
    Route::post('/vote/confirmer', 'submit')->name('vote.submit');
    Route::get('/vote/confirmation', 'confirmation')->name('vote.confirmation');
});

// Public, read-only results / in-room proclamation (revealed automatically at close).
Route::get('/resultats', [ResultsController::class, 'index'])->name('results.public');

/*
| Admin back-office (Phase 2)
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('candidates', CandidateController::class)
            ->except(['show'])->parameters(['candidates' => 'candidate']);

        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/import', [CompanyController::class, 'showImport'])->name('companies.import');
        Route::post('companies/import', [CompanyController::class, 'import'])->name('companies.import.store');

        Route::get('election', [ElectionController::class, 'edit'])->name('election.edit');
        Route::put('election', [ElectionController::class, 'update'])->name('election.update');
        Route::post('election/window', [ElectionController::class, 'toggleWindow'])->name('election.window');
        Route::post('election/qr', [ElectionController::class, 'toggleQr'])->name('election.qr.toggle');
        Route::post('election/runoff', [ElectionController::class, 'launchRunoff'])->name('election.runoff');
        Route::get('election/qr/fullscreen', [ElectionController::class, 'qrFullscreen'])->name('election.qr.fullscreen');
        Route::get('election/qr.svg', [ElectionController::class, 'qr'])->name('election.qr');

        Route::get('results', [ResultController::class, 'index'])->name('results.index');
        Route::get('results/export/excel', [ResultController::class, 'exportExcel'])->name('results.excel');
        Route::get('results/export/pdf', [ResultController::class, 'exportPdf'])->name('results.pdf');

        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
