<?php

namespace Database\Seeders;

use App\Models\GalleryItem;
use Illuminate\Database\Seeder;

class GalleryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GalleryItem::create([
            'title' => 'Kantor Pusat',
            'image' => 'gallery/kantor-1.jpg',
            'caption' => 'Ruang kerja tim Reka Mitra Teknologi di Jakarta.',
            'order' => 0,
        ]);

        GalleryItem::create([
            'title' => 'Tim Engineering',
            'image' => 'gallery/tim-1.jpg',
            'caption' => 'Tim engineering sedang code review mingguan.',
            'order' => 1,
        ]);

        GalleryItem::create([
            'title' => null,
            'image' => 'gallery/acara-1.jpg',
            'caption' => 'Suasana employee gathering tahunan.',
            'order' => 2,
        ]);

        GalleryItem::create([
            'title' => 'Diskusi Klien',
            'image' => 'gallery/diskusi-klien-1.jpg',
            'caption' => 'Sesi discovery bersama salah satu klien enterprise.',
            'order' => 3,
        ]);

        GalleryItem::create([
            'title' => null,
            'image' => 'gallery/workshop-1.jpg',
            'caption' => 'Workshop internal seputar praktik DevOps.',
            'order' => 4,
        ]);

        GalleryItem::create([
            'title' => 'Peluncuran Produk',
            'image' => 'gallery/peluncuran-1.jpg',
            'caption' => null,
            'order' => 5,
        ]);
    }
}
