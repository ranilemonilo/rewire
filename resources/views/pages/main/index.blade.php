<x-layouts::main
    :seo-description="$seoDescription"
    :analytics-id="$analyticsId"
    :company-name="$companyName"
    :company-tagline="$companyTagline"
    :social-links="$socialLinks"
>
    <x-landing.hero />
    <x-landing.trusted-by />
    <x-landing.services :services="$services" />
    <x-landing.gallery :gallery="$gallery" />
    <x-landing.infrastructure />
    <x-landing.stats />
    <x-landing.case-studies />
    <x-landing.process :about-page="$aboutPage" />
    <x-landing.testimonials />
    <x-landing.cta
        :contact-address="$contactAddress"
        :contact-email="$contactEmail"
        :contact-phone="$contactPhone"
    />
</x-layouts::main>
