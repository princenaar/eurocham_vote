<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ElectionController;
use App\Http\Controllers\Admin\ResultController;
use Illuminate\Support\Facades\Route;

/*
| Public
*/
Route::get('/', fn () => redirect()->route('vote.start'));

// Placeholder for the QR-gated voter flow (built in Phase 3).
Route::get('/vote', fn () => view('vote.placeholder'))->name('vote.start');

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
        Route::get('election/qr.svg', [ElectionController::class, 'qr'])->name('election.qr');

        Route::get('results', [ResultController::class, 'index'])->name('results.index');
        Route::get('results/export/excel', [ResultController::class, 'exportExcel'])->name('results.excel');
        Route::get('results/export/pdf', [ResultController::class, 'exportPdf'])->name('results.pdf');
    });
});
