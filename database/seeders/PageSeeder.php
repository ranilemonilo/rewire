<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Page::create([
            'title' => 'Tentang Kami',
            'excerpt' => 'PT. Reka Mitra Teknologi membantu perusahaan merancang, membangun, dan merawat solusi teknologi yang benar-benar dipakai, bukan sekadar demo.',
            'content' => "PT. Reka Mitra Teknologi berdiri untuk menjadi mitra teknologi jangka panjang bagi perusahaan yang ingin bertumbuh lewat digitalisasi yang tepat guna.\n\nKami memulai dari kebutuhan nyata klien -- bukan dari daftar teknologi yang sedang tren -- lalu merancang, membangun, dan merawat sistem yang benar-benar dipakai sehari-hari oleh tim di lapangan.\n\nDari pengembangan aplikasi web dan mobile, infrastruktur cloud, hingga konsultasi transformasi digital, tim kami bekerja sebagai perpanjangan tangan teknis klien, bukan sekadar vendor yang selesai begitu kontrak berakhir.",
            'is_published' => true,
        ]);
    }
}
