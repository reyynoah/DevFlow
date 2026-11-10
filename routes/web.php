<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\CommentController;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::resource('projects', ProjectController::class);

    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('members.store');
    Route::patch('projects/{project}/members/{user}', [ProjectMemberController::class, 'update'])->name('members.update');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');

Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
Route::post('tasks/{task}/comments', [CommentController::class, 'store'])->name('comments.store');
Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});