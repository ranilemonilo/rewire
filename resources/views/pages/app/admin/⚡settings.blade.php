<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Settings')] class extends Component
{
    public string $companyName = '';

    public string $companyTagline = '';

    public string $companyVision = '';

    public string $companyMission = '';

    public string $companyYearsExperience = '';

    public string $companyTotalClients = '';

    public string $companyTotalProjects = '';

    public string $seoDescription = '';

    public string $analyticsId = '';

    public string $socialLinkedin = '';

    public string $socialTwitter = '';

    public string $socialGithub = '';

    public string $socialInstagram = '';

    public string $contactAddress = '';

    public string $contactEmail = '';

    public string $contactPhone = '';

    public function mount(): void
    {
        $this->companyName = Setting::get('company_name', '') ?? '';
        $this->companyTagline = Setting::get('company_tagline', '') ?? '';
        $this->companyVision = Setting::get('company_vision', '') ?? '';
        $this->companyMission = Setting::get('company_mission', '') ?? '';
        $this->companyYearsExperience = Setting::get('company_years_experience', '') ?? '';
        $this->companyTotalClients = Setting::get('company_total_clients', '') ?? '';
        $this->companyTotalProjects = Setting::get('company_total_projects', '') ?? '';

        $this->seoDescription = Setting::get('seo_description', '') ?? '';
        $this->analyticsId = Setting::get('analytics_id', '') ?? '';
        $this->socialLinkedin = Setting::get('social_linkedin', '') ?? '';
        $this->socialTwitter = Setting::get('social_twitter', '') ?? '';
        $this->socialGithub = Setting::get('social_github', '') ?? '';
        $this->socialInstagram = Setting::get('social_instagram', '') ?? '';
        $this->contactAddress = Setting::get('contact_address', '') ?? '';
        $this->contactEmail = Setting::get('contact_email', '') ?? '';
        $this->contactPhone = Setting::get('contact_phone', '') ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'companyName' => ['required', 'string', 'max:255'],
            'companyTagline' => ['nullable', 'string', 'max:255'],
            'companyVision' => ['nullable', 'string'],
            'companyMission' => ['nullable', 'string'],
            'companyYearsExperience' => ['nullable', 'integer', 'min:0'],
            'companyTotalClients' => ['nullable', 'integer', 'min:0'],
            'companyTotalProjects' => ['nullable', 'integer', 'min:0'],
            'seoDescription' => ['nullable', 'string', 'max:255'],
            'analyticsId' => ['nullable', 'string', 'max:64'],
            'socialLinkedin' => ['nullable', 'url', 'max:255'],
            'socialTwitter' => ['nullable', 'url', 'max:255'],
            'socialGithub' => ['nullable', 'url', 'max:255'],
            'socialInstagram' => ['nullable', 'url', 'max:255'],
            'contactAddress' => ['nullable', 'string', 'max:255'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'contactPhone' => ['nullable', 'string', 'max:64'],
        ]);

        Setting::put('company_name', $this->companyName);
        Setting::put('company_tagline', $this->companyTagline);
        Setting::put('company_vision', $this->companyVision);
        Setting::put('company_mission', $this->companyMission);
        Setting::put('company_years_experience', $this->companyYearsExperience);
        Setting::put('company_total_clients', $this->companyTotalClients);
        Setting::put('company_total_projects', $this->companyTotalProjects);

        Setting::put('seo_description', $this->seoDescription);
        Setting::put('analytics_id', $this->analyticsId);
        Setting::put('social_linkedin', $this->socialLinkedin);
        Setting::put('social_twitter', $this->socialTwitter);
        Setting::put('social_github', $this->socialGithub);
        Setting::put('social_instagram', $this->socialInstagram);
        Setting::put('contact_address', $this->contactAddress);
        Setting::put('contact_email', $this->contactEmail);
        Setting::put('contact_phone', $this->contactPhone);

        Flux::toast(variant: 'success', text: 'Settings updated.');
    }
};
?>

<div class="w-full space-y-6">
    <div>
        <flux:heading size="xl">Site settings</flux:heading>
        <flux:subheading>App-wide values used across the public site.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">Company profile</flux:heading>
                <flux:subheading>Used across the public site's home, about, and footer sections.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="companyName" label="Company name" placeholder="PT. Reka Mitra Teknologi" />
                <flux:input wire:model="companyTagline" label="Tagline" placeholder="Mitra teknologi tepercaya untuk pertumbuhan bisnis Anda" />
            </div>

            <flux:textarea wire:model="companyVision" label="Vision" rows="3" />

            <flux:textarea wire:model="companyMission" label="Mission" rows="3" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <flux:input type="number" wire:model="companyYearsExperience" label="Years of experience" />
                <flux:input type="number" wire:model="companyTotalClients" label="Total clients" />
                <flux:input type="number" wire:model="companyTotalProjects" label="Total projects" />
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">SEO &amp; analytics</flux:heading>
                <flux:subheading>Applied site-wide on the public pages.</flux:subheading>
            </div>

            <flux:textarea
                wire:model="seoDescription"
                label="SEO meta description"
                description="Shown in search results and social previews. Falls back to nothing if left blank."
                rows="3"
            />

            <flux:input
                wire:model="analyticsId"
                label="Google Analytics measurement ID"
                description="e.g. G-XXXXXXXXXX. Leave blank to disable tracking."
            />
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">Social links</flux:heading>
                <flux:subheading>Shown in the public site footer. Leave any blank to hide that icon.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="socialLinkedin" label="LinkedIn URL" placeholder="https://linkedin.com/company/..." />
                <flux:input wire:model="socialTwitter" label="Twitter / X URL" placeholder="https://x.com/..." />
                <flux:input wire:model="socialGithub" label="GitHub URL" placeholder="https://github.com/..." />
                <flux:input wire:model="socialInstagram" label="Instagram URL" placeholder="https://instagram.com/..." />
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">Contact details</flux:heading>
                <flux:subheading>Shown in the landing page's call-to-action section.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <flux:input wire:model="contactAddress" label="Address" placeholder="Jakarta, Indonesia" />
                <flux:input wire:model="contactEmail" label="Email" placeholder="hello@example.com" />
                <flux:input wire:model="contactPhone" label="Phone" placeholder="+62 21 0000 0000" />
            </div>
        </flux:card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
