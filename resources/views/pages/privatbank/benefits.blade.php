@php
    $benefitsTitle = strip_tags(
        page_field($pageSlug, 'pb_benefits_title', 'Чому це вигідно?') ?? ''
    );

    $benefitsContent = page_field(
        $pageSlug,
        'pb_benefits_content',
        ''
    );
@endphp

<section class="px-4 py-16 sm:px-6 sm:py-20">
    <div class="mx-auto max-w-[1198px]">

        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-3xl font-black tracking-tight text-[#171d2d] sm:text-4xl">
                {{ $benefitsTitle }}
            </h2>
        </div>

        <div class="mt-10 rounded-[28px] border border-green-100 bg-white p-6 shadow-[0_18px_50px_rgba(15,23,42,0.07)] sm:p-10">

            <div class="prose prose-lg max-w-none prose-headings:font-black prose-headings:text-[#171d2d] prose-h3:text-2xl prose-p:leading-8 prose-li:my-3 prose-li:text-slate-600 prose-strong:text-[#171d2d] marker:text-green-600">
                {!! clean_html(
                    $benefitsContent,
                    'safe',
                    null,
                    '<p><br><h2><h3><h4><strong><em><ul><ol><li><a><img>'
                ) !!}
            </div>

        </div>
    </div>
</section>