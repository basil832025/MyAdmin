@php
    $conditionsTitle = strip_tags(
        page_field($pageSlug, 'pb_conditions_title', 'Умови оформлення') ?? ''
    );

    $conditionsContent = page_field(
        $pageSlug,
        'pb_conditions_content',
        ''
    );
@endphp

<section class="bg-[#173f21] px-4 py-16 text-white sm:px-6 sm:py-20">
    <div class="mx-auto max-w-[1198px]">

        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-3xl font-black tracking-tight sm:text-4xl">
                {{ $conditionsTitle }}
            </h2>
        </div>

        <div class="mt-10 rounded-[30px] border border-white/15 bg-white/10 p-5 backdrop-blur sm:p-9">

            <div class="rounded-[24px] bg-white p-6 text-[#171d2d] shadow-xl sm:p-10">
                <div class="prose prose-lg max-w-none prose-headings:font-black prose-headings:text-[#171d2d] prose-p:leading-8 prose-li:my-3 prose-li:text-slate-600 prose-strong:text-green-700 marker:text-green-600">
                    {!! clean_html(
                        $conditionsContent,
                        'safe',
                        null,
                        '<p><br><h2><h3><h4><strong><em><ul><ol><li><a>'
                    ) !!}
                </div>
            </div>

        </div>
    </div>
</section>