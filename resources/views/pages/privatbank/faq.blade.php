@php
    $faqTitle = match (app()->getLocale()) {
        'ru' => 'Часто задаваемые вопросы',
        'en' => 'Frequently asked questions',
        default => 'Поширені запитання',
    };

    $faqItems = match (app()->getLocale()) {
        'ru' => [
            [
                'question' => 'От какой суммы доступна оплата частями?',
                'answer' => 'Сервис доступен для заказов на сумму от 2000 грн.',
            ],
            [
                'question' => 'На сколько платежей можно разделить оплату?',
                'answer' => 'Максимальное количество платежей — 3.',
            ],
            [
                'question' => 'Что необходимо для оформления?',
                'answer' => 'Необходима карта ПриватБанка и доступный лимит сервиса «Оплата частями» или «Мгновенная рассрочка».',
            ],
            [
                'question' => 'Кто принимает решение о доступности сервиса?',
                'answer' => 'Окончательное решение принимает ПриватБанк согласно своим условиям и доступному лимиту клиента.',
            ],
        ],

        'en' => [
            [
                'question' => 'What is the minimum order amount?',
                'answer' => 'Installment payment is available for orders starting from UAH 2,000.',
            ],
            [
                'question' => 'How many payments can the order be divided into?',
                'answer' => 'The maximum number of payments is 3.',
            ],
            [
                'question' => 'What is required to use the service?',
                'answer' => 'You need a PrivatBank card and an available installment payment limit.',
            ],
            [
                'question' => 'Who decides whether the service is available?',
                'answer' => 'The final decision is made by PrivatBank according to its terms and the customer’s available limit.',
            ],
        ],

        default => [
            [
                'question' => 'Від якої суми доступна оплата частинами?',
                'answer' => 'Сервіс доступний для замовлень на суму від 2000 грн.',
            ],
            [
                'question' => 'На скільки платежів можна розділити оплату?',
                'answer' => 'Максимальна кількість платежів — 3.',
            ],
            [
                'question' => 'Що потрібно для оформлення?',
                'answer' => 'Потрібна картка ПриватБанку та доступний ліміт сервісу «Оплата частинами» або «Миттєва розстрочка».',
            ],
            [
                'question' => 'Хто приймає рішення про доступність сервісу?',
                'answer' => 'Остаточне рішення приймає ПриватБанк відповідно до власних умов і доступного ліміту клієнта.',
            ],
        ],
    };
@endphp

<section class="bg-white px-4 py-16 sm:px-6 sm:py-20">
    <div class="mx-auto max-w-4xl">

        <div class="text-center">
            <h2 class="text-3xl font-black tracking-tight text-[#171d2d] sm:text-4xl">
                {{ $faqTitle }}
            </h2>

            <p class="mx-auto mt-4 max-w-2xl leading-7 text-slate-600">
                {{ match (app()->getLocale()) {
                    'ru' => 'Коротко о главном перед оформлением заказа.',
                    'en' => 'Key information before placing your order.',
                    default => 'Коротко про головне перед оформленням замовлення.',
                } }}
            </p>
        </div>

        <div class="mt-10 space-y-4">
            @foreach ($faqItems as $item)
                <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-[#f8faf5] transition duration-300 open:border-green-200 open:bg-white open:shadow-[0_18px_45px_rgba(15,23,42,0.08)]">

                    <summary class="flex cursor-pointer list-none items-center justify-between gap-5 px-5 py-5 text-left text-base font-extrabold text-[#171d2d] sm:px-7 sm:py-6 sm:text-lg">

                        <span>
                            {{ $item['question'] }}
                        </span>

                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-green-100 text-xl font-normal text-green-700 transition duration-300 group-open:rotate-45 group-open:bg-green-600 group-open:text-white">
                            +
                        </span>
                    </summary>

                    <div class="px-5 pb-5 sm:px-7 sm:pb-6">
                        <div class="border-t border-slate-200 pt-5 leading-7 text-slate-600">
                            {{ $item['answer'] }}
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</section>