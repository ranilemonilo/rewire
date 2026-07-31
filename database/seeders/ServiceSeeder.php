<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::create([
            'title' => 'Pengembangan Aplikasi Web',
            'icon' => 'code',
            'description' => 'Membangun aplikasi web custom yang cepat, aman, dan mudah dirawat, mulai dari sistem internal hingga produk yang dipakai ribuan pengguna.',
            'order' => 0,
            'is_active' => true,
        ]);

        Service::create([
            'title' => 'Pengembangan Aplikasi Mobile',
            'icon' => 'compass',
            'description' => 'Merancang dan mengembangkan aplikasi mobile Android & iOS yang selaras dengan alur kerja bisnis klien, dari ide sampai rilis di store.',
            'order' => 1,
            'is_active' => true,
        ]);

        Service::create([
            'title' => 'Infrastruktur & Cloud',
            'icon' => 'cloud',
            'description' => 'Merancang, migrasi, dan mengelola infrastruktur cloud yang skalabel dan andal, sehingga tim klien fokus membangun produk, bukan mengurus server.',
            'order' => 2,
            'is_active' => true,
        ]);

        Service::create([
            'title' => 'Keamanan Sistem',
            'icon' => 'shield',
            'description' => 'Audit keamanan, hardening infrastruktur, dan praktik pengembangan aman untuk melindungi data dan operasional bisnis klien.',
            'order' => 3,
            'is_active' => true,
        ]);

        Service::create([
            'title' => 'Konsultasi IT & Transformasi Digital',
            'icon' => 'brain',
            'description' => 'Mendampingi perusahaan menyusun roadmap digitalisasi yang realistis, dari asesmen proses bisnis sampai eksekusi teknis.',
            'order' => 4,
            'is_active' => true,
        ]);

        Service::create([
            'title' => 'Pemeliharaan & Dukungan Sistem',
            'icon' => 'cog',
            'description' => 'Layanan maintenance, monitoring, dan dukungan teknis berkelanjutan agar sistem yang sudah berjalan tetap stabil dan up to date.',
            'order' => 5,
            'is_active' => true,
        ]);
    }
}
