<section id="gallery" class="scroll-mt-24 bg-white">
    <div class="mx-auto max-w-7xl px-6 py-24">
        <div class="flex flex-col gap-4 text-center">
            <span class="font-mono text-xs uppercase tracking-widest text-brand-accent">
                Gallery
            </span>

            <h2 class="font-display text-4xl font-semibold text-brand-navy">
                Our Recent Projects
            </h2>

            <p class="mx-auto max-w-2xl text-brand-navy/70">
                A collection of recent work, events, and moments from PT. Reka Mitra Teknologi.
            </p>
        </div>

        @if ($gallery->isEmpty())
            <div class="mt-16 rounded-2xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500">
                No gallery items have been published yet.
            </div>
        @else
            <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($gallery as $item)
                    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:shadow-lg">
                        <img
                            src="{{ asset('storage/'.$item->image) }}"
                            alt="{{ $item->title }}"
                            class="h-64 w-full object-cover"
                        >

                        <div class="p-5">
                            @if ($item->title)
                                <h3 class="text-lg font-semibold text-brand-navy">
                                    {{ $item->title }}
                                </h3>
                            @endif

                            @if ($item->caption)
                                <p class="mt-2 text-sm text-brand-navy/70">
                                    {{ $item->caption }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>