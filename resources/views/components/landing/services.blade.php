@props(['services'])

@php
    $services = $services ?? collect();

    // The Service model (title, icon, description) has no `category` or `tags`
    // columns, so those decorative labels aren't sourced from the CMS -- the
    // card only ever shows what admins can actually manage from
    // /content-management/services. The final "Ready to Extend" tile below is
    // a static contact prompt, not a service, so it stays hardcoded and is
    // always appended after the DB-backed cards.
@endphp

<section id="services" class="scroll-mt-24 bg-brand-snow">
    <div class="mx-auto max-w-7xl px-6 py-24">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="landing-reveal lg:w-3/5">
                <div class="flex items-center gap-3">
                    <span class="h-px w-8 bg-brand-accent"></span>
                    <span class="font-mono text-xs uppercase tracking-widest text-brand-accent">
                        01 &mdash; Capabilities
                    </span>
                </div>
                <h2 class="mt-4 font-display text-4xl font-semibold tracking-tight text-brand-navy sm:text-5xl">
                    Everything a client project starts with
                </h2>
            </div>
            <div class="landing-reveal landing-reveal-delay-1 lg:w-2/5">
                <p class="text-base text-brand-navy/70">
                    Foundational features every new project needs, already wired together so you can focus on what makes each client different.
                </p>
            </div>
        </div>

        <div class="mt-16 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                @php
                    $delay = ($loop->index % 4) + 1;
                @endphp

                <div
                    class="landing-card-hover landing-reveal landing-reveal-delay-{{ $delay }} flex flex-col rounded-2xl border border-brand-navy/10 bg-white p-8"
                >
                    <div class="flex size-11 items-center justify-center rounded-xl bg-brand-navy">
                        <x-landing.icon :name="$service->icon" class="size-5 text-brand-accent" />
                    </div>
                    <p class="mt-6 font-mono text-xs uppercase tracking-widest text-brand-navy/50">
                        {{ sprintf('%02d', $loop->iteration) }}
                    </p>
                    <h3 class="mt-2 font-display text-xl font-semibold text-brand-navy">
                        {{ $service->title }}
                    </h3>
                    <p class="mt-3 text-sm text-brand-navy/70">
                        {{ $service->description }}
                    </p>
                </div>
            @endforeach

            @php
                $ctaDelay = ($services->count() % 4) + 1;
            @endphp
            <a
                href="#contact"
                class="landing-card-hover landing-reveal landing-reveal-delay-{{ $ctaDelay }} group flex flex-col justify-between rounded-2xl bg-brand-navy p-8"
            >
                <div>
                    <div class="flex size-11 items-center justify-center rounded-xl bg-brand-snow/10">
                        <x-landing.icon name="compass" class="size-5 text-brand-accent" />
                    </div>
                    <p class="mt-6 font-mono text-xs uppercase tracking-widest text-brand-silver">
                        {{ sprintf('%02d', $services->count() + 1) }}
                    </p>
                    <h3 class="mt-2 font-display text-xl font-semibold text-brand-snow">
                        Ready to Extend
                    </h3>
                    <p class="mt-3 text-sm text-brand-silver">
                        Add billing, media, or notifications when a project actually needs them.
                    </p>
                </div>
                <span class="mt-8 inline-flex items-center gap-2 text-sm font-medium text-brand-accent">
                    Get in touch
                    <x-landing.icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" />
                </span>
            </a>
        </div>
    </div>
</section>
