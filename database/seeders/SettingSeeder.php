<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::put('contact_address', 'Jakarta, Indonesia');
        Setting::put('contact_email', 'hello@recodex.id');
        Setting::put('contact_phone', '+62 21 0000 0000');

        Setting::put('company_name', 'PT. Reka Mitra Teknologi');
        Setting::put('company_tagline', 'Mitra teknologi tepercaya untuk pertumbuhan bisnis Anda');
        Setting::put('company_vision', 'Menjadi mitra teknologi pilihan utama bagi perusahaan Indonesia yang ingin bertumbuh lewat digitalisasi yang tepat guna.');
        Setting::put('company_mission', 'Merancang, membangun, dan merawat solusi teknologi yang andal, aman, dan benar-benar dipakai -- dengan tim yang bekerja sebagai perpanjangan tangan teknis klien.');
        Setting::put('company_years_experience', '8');
        Setting::put('company_total_clients', '45');
        Setting::put('company_total_projects', '120');
    }
}
