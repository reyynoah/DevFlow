<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data dummy DevFlow (versi ringkas): 3 user dan 3 project lengkap dengan task, komentar, dan log aktivitas.
 *
 * Jalankan:  php artisan db:seed --class=DemoSeeder
 *
 * Aman dijalankan ulang: project dengan nama yang sama tidak akan dibuat dua kali.
 * Data lama (misalnya project "tile-hause") tidak disentuh.
 */
class DemoSeeder extends Seeder
{
    /** Akun yang jadi pemilik utama project (ganti kalau perlu). */
    private const OWNER_EMAIL = 'noahrey74@gmail.com';

    private const STATUS_LABEL = [
        'todo'        => 'To Do',
        'in_progress' => 'In Progress',
        'review'      => 'Review',
        'done'        => 'Done',
    ];

    public function run(): void
    {
        $me = User::where('email', self::OWNER_EMAIL)->first() ?? User::orderBy('id')->first();

        if (! $me) {
            $this->command?->warn('Belum ada user. Daftar dulu lewat halaman register, lalu jalankan seeder ini lagi.');
            return;
        }

        // Total 3 user: kamu + 2 rekan satu tim (password semuanya: "password")
        $people = ['me' => $me];
        $team = [
            'dina' => ['Dina Pratiwi', 'dina@devflow.test'],
            'budi' => ['Budi Santoso', 'budi@devflow.test'],
        ];
        foreach ($team as $key => [$name, $email]) {
            $people[$key] = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'password']);
        }

        $created = 0;
        foreach ($this->projects() as $data) {
            $creator = $people[$data['creator']];

            $exists = Project::where('name', $data['name'])->where('created_by', $creator->id)->exists();
            if ($exists) {
                $this->command?->line("Lewati (sudah ada): {$data['name']}");
                continue;
            }

            $this->buildProject($data, $people, $creator);
            $created++;
            $this->command?->info("Dibuat: {$data['name']}");
        }

        $this->command?->info("Selesai. {$created} project baru untuk {$me->name}.");
    }

    private function buildProject(array $data, array $people, User $creator): void
    {
        $startAt = now()->startOfDay()->addDays($data['start'])->setHour(9);

        $project = new Project();
        $project->forceFill([
            'name'        => $data['name'],
            'description' => $data['description'],
            'category'    => $data['category'],
            'priority'    => $data['priority'],
            'start_date'  => $startAt->toDateString(),
            'deadline'    => now()->startOfDay()->addDays($data['deadline'])->toDateString(),
            'created_by'  => $creator->id,
            'created_at'  => $startAt,
            'updated_at'  => $startAt,
        ])->save();

        // Anggota tim: pembuat selalu manager
        $project->members()->attach($creator->id, ['role' => 'manager']);
        $this->log($project, $creator, $creator->name . ' membuat project ini', $startAt);

        $minute = 5;
        foreach ($data['members'] as $key => $role) {
            $user = $people[$key];
            if ($user->id === $creator->id) {
                continue;
            }
            $project->members()->attach($user->id, ['role' => $role]);
            $this->log(
                $project,
                $creator,
                $creator->name . ' menambahkan ' . $user->name . ' sebagai ' . $role,
                $startAt->copy()->addMinutes($minute)
            );
            $minute += 5;
        }

        // Task
        $daysSinceStart = max(1, abs($data['start']));
        foreach ($data['tasks'] as $i => $row) {
            [$title, $desc, $status, $priority, $dueOffset, $assigneeKey] = $row;
            $commentRows = $row[6] ?? [];

            $assignee  = $assigneeKey ? $people[$assigneeKey] : null;
            $actor     = $creator;
            $createdAt = $startAt->copy()->addDays(min($i * 2, $daysSinceStart - 1))->setHour(10);

            $task = new Task();
            $task->forceFill([
                'project_id'  => $project->id,
                'title'       => $title,
                'description' => $desc,
                'status'      => $status,
                'priority'    => $priority,
                'deadline'    => $dueOffset === null ? null : now()->startOfDay()->addDays($dueOffset)->toDateString(),
                'assigned_to' => $assignee?->id,
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ])->save();

            $this->log($project, $actor, $actor->name . ' membuat task "' . $title . '"', $createdAt);

            // Jejak perpindahan status (To Do -> In Progress -> Review -> Done)
            $flow  = ['todo', 'in_progress', 'review', 'done'];
            $steps = array_search($status, $flow, true);
            $mover = $assignee ?? $actor;
            for ($s = 1; $s <= $steps; $s++) {
                $at = $this->clamp($createdAt->copy()->addDays($s)->addHours(3));
                $this->log(
                    $project,
                    $mover,
                    $mover->name . ' memindahkan task "' . $title . '" dari '
                        . self::STATUS_LABEL[$flow[$s - 1]] . ' ke ' . self::STATUS_LABEL[$flow[$s]],
                    $at
                );
            }

            // Komentar
            foreach ($commentRows as [$userKey, $body, $hoursAgo]) {
                $at = now()->subHours($hoursAgo);

                $comment = new Comment();
                $comment->forceFill([
                    'task_id'    => $task->id,
                    'user_id'    => $people[$userKey]->id,
                    'body'       => $body,
                    'created_at' => $at,
                    'updated_at' => $at,
                ])->save();

                $this->log($project, $people[$userKey], $people[$userKey]->name . ' mengomentari task "' . $title . '"', $at);
            }
        }
    }

    private function log(Project $project, User $user, string $text, $at): void
    {
        $at = $this->clamp($at);

        (new ActivityLog())->forceFill([
            'project_id'  => $project->id,
            'user_id'     => $user->id,
            'description' => $text,
            'created_at'  => $at,
            'updated_at'  => $at,
        ])->save();
    }

    /** Jangan sampai ada waktu di masa depan. */
    private function clamp($at)
    {
        return $at->isFuture() ? now()->subMinutes(random_int(5, 90)) : $at;
    }

    /**
     * Format task: [judul, deskripsi, status, prioritas, deadline (hari dari hari ini), penanggung jawab, komentar?]
     * Komentar: [[user, isi, berapa jam lalu], ...]
     * Key user yang tersedia: me, dina, budi
     */
    private function projects(): array
    {
        return [

            // 1 ------------------------------------------------------------
            [
                'name'        => 'Redesign Website Company Profile',
                'description' => 'Merombak tampilan website perusahaan agar lebih modern, cepat, dan nyaman dibuka di semua perangkat.',
                'category'    => 'Web Design',
                'priority'    => 'high',
                'start'       => -30,
                'deadline'    => 14,
                'creator'     => 'me',
                'members'     => ['me' => 'manager', 'dina' => 'manager', 'budi' => 'member'],
                'tasks'       => [
                    ['Riset kompetitor dan referensi desain', 'Kumpulkan 5 website kompetitor, catat kelebihan dan kekurangannya.', 'done', 'medium', -24, 'dina'],
                    ['Desain UI halaman Beranda (Figma)', 'Hero, keunggulan layanan, testimoni, dan footer.', 'done', 'high', -16, 'dina', [
                        ['me', 'Warna hero sudah oke, tapi tombol CTA kurang kontras. Coba pakai warna yang lebih gelap ya.', 120],
                        ['dina', 'Siap, sudah kuperbaiki dan kuunggah ulang ke Figma.', 110],
                    ]],
                    ['Slicing halaman Beranda ke Blade', 'Ubah desain Beranda jadi template Blade yang responsif.', 'done', 'high', -8, 'me'],
                    ['Slicing halaman Layanan', 'Pasang desain Layanan, pastikan grid rapi di layar kecil.', 'review', 'medium', 2, 'me'],
                    ['Form kontak dengan validasi dan kirim email', 'Validasi di server, kirim notifikasi ke email admin.', 'in_progress', 'high', 3, 'budi', [
                        ['me', 'Jangan lupa tambah rate limit supaya tidak kena spam.', 30],
                    ]],
                    ['Optimasi gambar dan lazy loading', 'Kompres gambar dan tunda pemuatan gambar di bawah layar.', 'in_progress', 'low', -2, 'budi', [
                        ['me', 'Ini sudah lewat deadline, ada kendala? Kalau butuh bantuan kabari ya.', 20],
                        ['budi', 'Masih ada sekitar 30 gambar yang belum dikompres. Besok kuselesaikan.', 14],
                    ]],
                    ['Testing responsif di HP dan tablet', 'Cek tampilan di ukuran layar 360, 768, dan 1024.', 'todo', 'medium', 8, 'dina'],
                    ['Deploy ke server staging', 'Siapkan staging untuk review klien.', 'todo', 'high', 12, 'me'],
                ],
            ],

            // 2 ------------------------------------------------------------
            [
                'name'        => 'Aplikasi Kasir Toko Kelontong',
                'description' => 'Aplikasi kasir berbasis web untuk mencatat penjualan, stok barang, dan laporan harian toko.',
                'category'    => 'Web App',
                'priority'    => 'high',
                'start'       => -20,
                'deadline'    => 30,
                'creator'     => 'me',
                'members'     => ['me' => 'manager', 'budi' => 'member', 'dina' => 'member'],
                'tasks'       => [
                    ['Rancang ERD dan struktur database', 'Tabel barang, kategori, transaksi, dan detail transaksi.', 'done', 'high', -16, 'me'],
                    ['CRUD data barang dan kategori', 'Tambah, ubah, hapus barang lengkap dengan foto.', 'done', 'medium', -9, 'budi'],
                    ['Halaman transaksi penjualan', 'Keranjang, tambah barang cepat, dan total belanja.', 'in_progress', 'high', 2, 'me', [
                        ['budi', 'Untuk keranjang, pakai session atau simpan langsung ke database?', 48],
                        ['me', 'Simpan ke session dulu, baru ke database saat pembayaran dikonfirmasi.', 46],
                    ]],
                    ['Perhitungan kembalian dan diskon', 'Diskon persen dan nominal, plus pembulatan.', 'review', 'high', -1, 'budi', [
                        ['budi', 'Diskon persen dan nominal sudah jalan. Tinggal tes kasus pembulatan.', 26],
                    ]],
                    ['Cetak struk (printer thermal 58mm)', 'Template struk ringkas dan siap cetak.', 'todo', 'medium', 9, 'dina'],
                    ['Laporan penjualan harian dan bulanan', 'Ringkasan omzet, barang terlaris, dan ekspor ke Excel.', 'todo', 'high', 18, 'me'],
                    ['Backup database otomatis', 'Jadwalkan backup harian ke penyimpanan terpisah.', 'todo', 'low', 25, null],
                ],
            ],

            // 3 (dibuat Dina, kamu jadi member) -----------------------------
            [
                'name'        => 'Sistem Booking Lapangan Futsal',
                'description' => 'Platform pemesanan lapangan futsal online: jadwal real-time, pembayaran, dan konfirmasi otomatis.',
                'category'    => 'Web App',
                'priority'    => 'medium',
                'start'       => -15,
                'deadline'    => 45,
                'creator'     => 'dina',
                'members'     => ['dina' => 'manager', 'me' => 'member', 'budi' => 'member'],
                'tasks'       => [
                    ['Analisis kebutuhan dan user story', 'Wawancara pengelola lapangan dan rangkum kebutuhan.', 'done', 'medium', -11, 'dina'],
                    ['Halaman jadwal lapangan (kalender)', 'Tampilkan slot kosong dan terisi per hari.', 'in_progress', 'high', 5, 'me'],
                    ['Form pemesanan dan validasi bentrok jadwal', 'Cegah dua pemesanan di jam yang sama.', 'in_progress', 'high', 7, 'me', [
                        ['dina', 'Validasi bentrok jadwal wajib dicek di sisi server, jangan hanya di JavaScript.', 22],
                        ['me', 'Siap, kutaruh di controller sebelum data disimpan.', 18],
                    ]],
                    ['Integrasi pembayaran Midtrans (sandbox)', 'Alur bayar, callback, dan update status pesanan.', 'todo', 'high', 20, 'budi'],
                    ['Dashboard admin: kelola lapangan dan harga', 'CRUD lapangan, jam operasional, dan tarif.', 'todo', 'medium', 30, 'dina'],
                    ['Halaman riwayat pesanan pelanggan', 'Daftar pesanan lama dan status pembayarannya.', 'todo', 'low', 35, null],
                ],
            ],
        ];
    }
}