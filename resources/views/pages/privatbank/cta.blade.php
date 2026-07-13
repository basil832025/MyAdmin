@php
    $ctaContent = page_field(
        $pageSlug,
        'pb_cta',
        ''
    );
@endphp

@if (trim(strip_tags($ctaContent ?? '')) !== '')
    <section class="relative overflow-hidden border-b border-green-800/10 bg-gradient-to-br from-[#78c832] via-[#49b939] to-[#159447] px-4 py-20 text-center text-white sm:px-6 sm:py-24">

        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -right-32 -top-40 h-96 w-96 rounded-full bg-white/15 blur-3xl"></div>

            <div class="absolute -bottom-44 -left-28 h-96 w-96 rounded-full bg-yellow-200/15 blur-3xl"></div>

            <div
                class="absolute inset-0 opacity-[0.06]"
                style="background-image: radial-gradient(white 0.8px, transparent 0.8px); background-size: 26px 26px;"
            ></div>
        </div>

        <div class="relative mx-auto max-w-4xl">
            <div class="prose prose-lg mx-auto max-w-none prose-headings:font-black prose-headings:text-white prose-h2:mx-auto prose-h2:max-w-3xl prose-h2:text-3xl prose-h2:leading-tight prose-p:mx-auto prose-p:max-w-2xl prose-p:leading-8 prose-p:text-white/90 prose-a:mt-3 prose-a:inline-flex prose-a:items-center prose-a:justify-center prose-a:rounded-full prose-a:bg-white prose-a:px-9 prose-a:py-4 prose-a:font-extrabold prose-a:text-[#185c2c] prose-a:no-underline prose-a:shadow-[0_16px_35px_rgba(11,79,38,0.22)] prose-a:transition prose-a:duration-300 hover:prose-a:-translate-y-1 hover:prose-a:bg-green-50 hover:prose-a:shadow-[0_22px_45px_rgba(11,79,38,0.3)] sm:prose-h2:text-5xl">
                {!! clean_html(
                    $ctaContent,
                    'safe',
                    null,
                    '<p><br><h2><h3><strong><em><a>'
                ) !!}
            </div>
        </div>
    </section>

    <div class="h-8 bg-white sm:h-12"></div>
@endif