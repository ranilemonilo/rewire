<?php

use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

test('non-admin cannot access the settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.settings'))->assertForbidden();
});

test('admin can view and save settings', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    $this->get(route('admin.settings'))->assertOk();

    Livewire::test('pages::app.admin.settings')
        ->set('companyName', 'PT. Reka Mitra Teknologi')
        ->set('seoDescription', 'A starter kit for client projects.')
        ->set('analyticsId', 'G-ABC1234567')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('seo_description'))->toBe('A starter kit for client projects.');
    expect(Setting::get('analytics_id'))->toBe('G-ABC1234567');
});

test('admin can save the company profile', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.settings')
        ->set('companyName', 'PT. Reka Mitra Teknologi')
        ->set('companyTagline', 'Mitra teknologi tepercaya untuk pertumbuhan bisnis Anda')
        ->set('companyVision', 'Menjadi mitra teknologi pilihan utama bagi perusahaan Indonesia.')
        ->set('companyMission', 'Merancang, membangun, dan merawat solusi teknologi yang andal.')
        ->set('companyYearsExperience', '8')
        ->set('companyTotalClients', '45')
        ->set('companyTotalProjects', '120')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('company_name'))->toBe('PT. Reka Mitra Teknologi');
    expect(Setting::get('company_tagline'))->toBe('Mitra teknologi tepercaya untuk pertumbuhan bisnis Anda');
    expect(Setting::get('company_vision'))->toBe('Menjadi mitra teknologi pilihan utama bagi perusahaan Indonesia.');
    expect(Setting::get('company_mission'))->toBe('Merancang, membangun, dan merawat solusi teknologi yang andal.');
    expect(Setting::get('company_years_experience'))->toBe('8');
    expect(Setting::get('company_total_clients'))->toBe('45');
    expect(Setting::get('company_total_projects'))->toBe('120');
});

test('saving the company profile with a non-numeric stat fails validation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.settings')
        ->set('companyName', 'PT. Reka Mitra Teknologi')
        ->set('companyYearsExperience', 'not-a-number')
        ->call('save')
        ->assertHasErrors(['companyYearsExperience' => 'integer']);
});

test('editing the company name logs activity with a human-friendly label', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.settings')
        ->set('companyName', 'PT. Reka Mitra Teknologi')
        ->call('save');

    $activity = Activity::query()
        ->where('description', 'like', 'Company name%')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('Company name was created');
});
test('admin can save contact details', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.settings')
        ->set('companyName', 'PT. Reka Mitra Teknologi')
        ->set('contactAddress', 'Jakarta, Indonesia')
        ->set('contactEmail', 'hello@recodex.id')
        ->set('contactPhone', '+62 21 0000 0000')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('contact_address'))->toBe('Jakarta, Indonesia');
    expect(Setting::get('contact_email'))->toBe('hello@recodex.id');
    expect(Setting::get('contact_phone'))->toBe('+62 21 0000 0000');
});

test('admin can save all four social links', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.settings')
        ->set('companyName', 'PT. Reka Mitra Teknologi')
        ->set('socialLinkedin', 'https://linkedin.com/company/recodex')
        ->set('socialTwitter', 'https://x.com/recodex')
        ->set('socialGithub', 'https://github.com/recodex')
        ->set('socialInstagram', 'https://instagram.com/recodex')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('social_linkedin'))->toBe('https://linkedin.com/company/recodex');
    expect(Setting::get('social_twitter'))->toBe('https://x.com/recodex');
    expect(Setting::get('social_github'))->toBe('https://github.com/recodex');
    expect(Setting::get('social_instagram'))->toBe('https://instagram.com/recodex');
});

test('an invalid social link URL fails validation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.settings')
        ->set('companyName', 'PT. Reka Mitra Teknologi')
        ->set('socialLinkedin', 'not-a-valid-url')
        ->call('save')
        ->assertHasErrors(['socialLinkedin' => 'url']);
});

test('an invalid contact email fails validation', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.settings')
        ->set('companyName', 'PT. Reka Mitra Teknologi')
        ->set('contactEmail', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['contactEmail' => 'email']);
});
