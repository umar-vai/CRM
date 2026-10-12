<?php

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\IndustryPackageController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [WorkspaceController::class, 'index'])->name('dashboard');
    Route::post('workspaces', [WorkspaceController::class, 'store'])->middleware('throttle:10,1')->name('workspaces.store');
    Route::get('join/{tenant}/{invitation}', [InvitationController::class, 'show'])->middleware(['sensitive', 'throttle:30,1'])->name('invitations.show');
    Route::post('join/{tenant}/{invitation}', [InvitationController::class, 'accept'])->middleware(['sensitive', 'throttle:10,1'])->name('invitations.accept');
    Route::prefix('workspaces/{tenant}')->middleware('workspace')->group(function () {
        Route::get('/', [WorkspaceController::class, 'show'])->name('workspaces.show');
        Route::get('leads', [LeadController::class, 'index'])->middleware('workspace.permission:leads.view')->name('leads.index');
        Route::post('leads', [LeadController::class, 'store'])->middleware('workspace.permission:leads.create')->name('leads.store');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->middleware(['workspace.permission:leads.view', 'sensitive'])->name('leads.show');
        Route::patch('leads/{lead}', [LeadController::class, 'update'])->middleware('workspace.permission:leads.update')->name('leads.update');
        Route::post('leads/{lead}/activities', [LeadController::class, 'activity'])->middleware(['workspace.permission:leads.activities.create', 'throttle:30,1'])->name('leads.activity');
        Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->middleware(['workspace.permission:leads.convert', 'throttle:10,1'])->name('leads.convert');
        Route::get('customers', [CustomerController::class, 'index'])->middleware(['workspace.permission:customers.view', 'sensitive'])->name('customers.index');
        Route::get('configuration', [ConfigurationController::class, 'index'])->middleware('workspace.permission:configuration.publish')->name('configuration.index');
        Route::get('configuration/packages', [IndustryPackageController::class, 'index'])->middleware(['workspace.permission:configuration.preview', 'sensitive', 'throttle:30,1'])->name('configuration.packages');
        Route::post('configuration', [ConfigurationController::class, 'store'])->middleware('workspace.permission:configuration.publish')->name('configuration.store');
        Route::middleware(['workspace.permission:team.manage', 'sensitive'])->group(function () {
            Route::get('team', [TeamController::class, 'index'])->name('team.index');
            Route::post('team/invitations', [TeamController::class, 'invite'])->middleware('throttle:10,1')->name('team.invite');
            Route::delete('team/invitations/{invitation}', [TeamController::class, 'revoke'])->middleware('throttle:30,1')->name('team.revoke');
            Route::patch('team/members/{member}', [TeamController::class, 'update'])->middleware('throttle:30,1')->name('team.update');
            Route::delete('team/members/{member}', [TeamController::class, 'destroy'])->middleware('throttle:30,1')->name('team.destroy');
        });
    });
});

require __DIR__.'/settings.php';
