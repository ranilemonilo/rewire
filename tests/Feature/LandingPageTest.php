<?php

use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;
use App\Models\GalleryItem;

test('landing page renders successfully with the expected content', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Ship your next');
    $response->assertSee('client project');
});

test('landing page renders SEO description and analytics script from settings', function () {
    Setting::put('seo_description', 'A starter kit for client projects.');
    Setting::put('analytics_id', 'G-ABC1234567');

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('A starter kit for client projects.', false);
    $response->assertSee('G-ABC1234567', false);
});

test('landing page omits SEO meta and analytics script when settings are empty', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('name="description"', false);
    $response->assertDontSee('googletagmanager.com', false);
});

test('landing page lists active services from the database and hides inactive ones', function () {
    Service::create(['title' => 'Active Service', 'icon' => 'code', 'description' => 'An active service.', 'order' => 0, 'is_active' => true]);
    Service::create(['title' => 'Inactive Service', 'icon' => 'cog', 'description' => 'An inactive service.', 'order' => 1, 'is_active' => false]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Active Service');
    $response->assertDontSee('Inactive Service');
});

test('landing page about section shows the first published page', function () {
    Page::create(['title' => 'Tentang Kami', 'excerpt' => 'Ringkasan singkat perusahaan.', 'content' => 'Isi lengkap.', 'is_published' => true]);
    Page::create(['title' => 'Draft Page', 'excerpt' => 'Belum terbit.', 'content' => 'Isi lengkap.', 'is_published' => false]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Tentang Kami');
    $response->assertSee('Ringkasan singkat perusahaan.');
    $response->assertDontSee('Draft Page');
});

test('landing page CTA section shows contact details from settings', function () {
    Setting::put('contact_address', 'Jakarta, Indonesia');
    Setting::put('contact_email', 'hello@example.test');
    Setting::put('contact_phone', '+62 21 0000 0000');

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Jakarta, Indonesia');
    $response->assertSee('hello@example.test');
    $response->assertSee('+62 21 0000 0000');
});

test('site footer shows the company name and tagline from settings', function () {
    Setting::put('company_name', 'PT. Reka Mitra Teknologi');
    Setting::put('company_tagline', 'Mitra teknologi tepercaya untuk pertumbuhan bisnis Anda');

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('PT. Reka Mitra Teknologi');
    $response->assertSee('Mitra teknologi tepercaya untuk pertumbuhan bisnis Anda');
});

test('landing page about section shows company vision and mission when set', function () {
    Setting::put('company_vision', 'To be the leading technology partner in Indonesia.');
    Setting::put('company_mission', 'Delivering reliable software that helps businesses grow.');

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('To be the leading technology partner in Indonesia.');
    $response->assertSee('Delivering reliable software that helps businesses grow.');
});

test('landing page about section omits vision and mission when not set', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Vision');
    $response->assertDontSee('Mission');
});

test('landing page stats section shows company achievement numbers from settings', function () {
    Setting::put('company_years_experience', '8');
    Setting::put('company_total_clients', '45');
    Setting::put('company_total_projects', '120');

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('data-target="8"', false);
    $response->assertSee('data-target="45"', false);
    $response->assertSee('data-target="120"', false);
});

test('landing page lists gallery items from the database', function () {
    GalleryItem::create(['title' => 'Team Offsite', 'image' => 'gallery/offsite.jpg', 'order' => 0]);
    GalleryItem::create(['title' => 'Office Launch', 'image' => 'gallery/launch.jpg', 'order' => 1]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Team Offsite');
    $response->assertSee('Office Launch');
});

test('landing page gallery section shows at most 6 items even when more exist', function () {
    foreach (range(1, 8) as $i) {
        GalleryItem::create(['title' => "Photo {$i}", 'image' => "gallery/photo-{$i}.jpg", 'order' => $i]);
    }

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Photo 1');
    $response->assertSee('Photo 6');
    $response->assertDontSee('Photo 7');
    $response->assertDontSee('Photo 8');
});
