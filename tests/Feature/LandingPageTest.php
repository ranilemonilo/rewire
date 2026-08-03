<?php

use App\Models\Page;
use App\Models\Service;
use App\Models\Setting;

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
