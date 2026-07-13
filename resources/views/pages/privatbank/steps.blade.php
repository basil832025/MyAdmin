@php
    $stepsTitle = strip_tags(
        page_field(
            $pageSlug,
            'pb_steps_title',
            'Як оформити оплату частинами?'
        ) ?? ''
    );

    $stepsContent = page_field(
        $pageSlug,
        'pb_steps_content',
        ''
    );
@endphp

<section id="how-it-works" class="scroll-mt-24 px-4 py-16 sm:px-6 sm:py-20">
    <div class="mx-auto max-w-[1198px]">

        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-3xl font-black tracking-tight text-[#171d2d] sm:text-4xl">
                {{ $stepsTitle }}
            </h2>
        </div>

        <div class="mt-10 rounded-[28px] border border-orange-100 bg-[#fffaf3] p-6 shadow-sm sm:p-10">

            <div class="prose prose-lg max-w-none prose-headings:font-black prose-headings:text-[#171d2d] prose-p:leading-8 prose-ol:space-y-4 prose-li:rounded-2xl prose-li:bg-white prose-li:px-5 prose-li:py-4 prose-li:text-slate-600 prose-li:shadow-sm prose-strong:text-[#171d2d] marker:font-black marker:text-[#ff7500]">
                {!! clean_html(
                    $stepsContent,
                    'safe',
                    null,
                    '<p><br><h2><h3><h4><strong><em><ul><ol><li><a>'
                ) !!}
            </div>

        </div>
    </div>
</section>