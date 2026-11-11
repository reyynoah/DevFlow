<?php

// Mengimpor class pengatur rute dan semua Controller yang digunakan
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;

// Mengarahkan pengunjung dari halaman utama (/) langsung ke halaman login
Route::redirect('/', '/login');

// Menampilkan halaman Dashboard
// Middleware 'auth' memastikan hanya yang sudah login yang bisa masuk
// Middleware 'verified' memastikan email pengguna sudah diverifikasi (jika diaktifkan)
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Kelompok rute untuk mengatur Profil Pengguna (wajib login)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');       // Menampilkan form edit profil
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update'); // Memperbarui data profil
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy'); // Menghapus akun pengguna
});

// Memanggil rute-rute autentikasi bawaan Laravel (login, register, reset password, dll.)
require __DIR__.'/auth.php';

// Kelompok rute utama untuk fitur aplikasi (semua wajib login)
Route::middleware('auth')->group(function () {
    
    // Fitur Manajemen Proyek
    // Route::resource otomatis membuat rute untuk index, create, store, show, edit, update, dan destroy
    Route::resource('projects', ProjectController::class);

    // Fitur Manajemen Tugas (Task)
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store'); // Membuat tugas baru di dalam sebuah proyek
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');           // Halaman form edit tugas
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');            // Menyimpan perubahan tugas
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');       // Menghapus tugas
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status'); // Mengubah status tugas (misal: To-Do -> Done)
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');                // Melihat detail sebuah tugas

    // Fitur Kolaborasi Anggota Proyek (Project Members)
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('members.store'); // Menambahkan anggota ke proyek
    Route::patch('projects/{project}/members/{user}', [ProjectMemberController::class, 'update'])->name('members.update'); // Mengubah akses/peran anggota
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('members.destroy'); // Mengeluarkan anggota dari proyek

    // Fitur Komentar pada Tugas
    Route::post('tasks/{task}/comments', [CommentController::class, 'store'])->name('comments.store'); // Mengirim komentar baru di sebuah tugas
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy'); // Menghapus komentar
});