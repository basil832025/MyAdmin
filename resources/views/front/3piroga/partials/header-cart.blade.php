@php
//dd($cartQty);
    $isGuestPageCacheCandidate = !auth()->check() && (bool) request()->route('page_cache_candidate', false);
    $cartQty = (int) ($cartQty ?? ($isGuestPageCacheCandidate ? 0 : (app(\App\Services\CartService::class)->info()['qty'] ?? 0)));
    $locale = app()->getLocale();
    $isLocalized = in_array($locale, ['ru', 'en'], true);
    $cartPageUrl = $isLocalized
        ? route('localized.cart.page', ['locale' => $locale])
        : route('cart.page');
    $cartSidebarUrl = $isLocalized
        ? route('localized.cart.sidebar', ['locale' => $locale])
        : route('cart.sidebar');
//dd($cartQty,app(\App\Services\CartService::class)->info()['qty'] );
@endphp
<div
    x-data="{
    isOpen:false, url:null, hdr:0,
    async open(u){ this.calcHeader(); this.isOpen=true; this.url=u; await this.load(u) },
    close(){ this.isOpen=false; this.url=null; },
    calcHeader(){ const el=document.getElementById('site-header'); this.hdr=el?Math.round(el.getBoundingClientRect().height):0 },
    async load(u){
      const box = this.$refs.box;
      box.innerHTML = '<div class=\'p-6 text-center text-gray-500\'>{{ st('cart.zavantazhennya', 'Завантаження') }}…</div>';
const html = await fetch(u, { headers:{'Accept':'text/html'} }).then(r=>r.text());
box.innerHTML = html;
}
}"
x-init="isOpen = false; url = null; $nextTick(() => { isOpen = false; close(); }); $el.addEventListener('cart-reload', () => { if (isOpen && url) load(url) }); window.addEventListener('cart-reload', () => { if (isOpen && url) load(url) }); window.addEventListener('popstate', () => { close(); }); document.addEventListener('visibilitychange', () => { if (!document.hidden && isOpen) { close(); } });"
>

    {{-- иконка --}}
    <a href="{{ $cartPageUrl }}"
       class="group relative flex items-center justify-center w-5 h-5 text-[#19191A] hover:text-[#FF7500]"
       data-url="{{ $cartSidebarUrl }}"
       data-cart-page="{{ $cartPageUrl }}"
       @click.prevent="
           const isMobile = window.innerWidth < 1024;
           if (isMobile) {
               window.location.href = $event.currentTarget.dataset.cartPage;
           } else {
               open($event.currentTarget.dataset.url);
           }
       ">
        <svg class="w-5 h-5 shrink-0 flex-none" width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M0.5 1.62307C0.5 1.39574 0.590312 1.17773 0.751069 1.01698C0.911825 0.856236 1.12986 0.76593 1.3572 0.76593H1.99496C3.08075 0.76593 3.73223 1.49622 4.10368 2.17507C4.3517 2.62764 4.53114 3.15222 4.67172 3.62764L4.78601 3.62307H19.0704C20.0191 3.62307 20.7048 4.5305 20.4443 5.44364L18.355 12.7682C18.1676 13.4252 17.7713 14.0032 17.2261 14.4149C16.6808 14.8265 16.0162 15.0492 15.333 15.0494H8.53485C7.84622 15.0494 7.17664 14.8233 6.62903 14.4058C6.08141 13.9883 5.68608 13.4025 5.50378 12.7385L4.63515 9.5705L3.19504 4.71564L3.1939 4.7065C3.0156 4.0585 2.84874 3.45164 2.59958 2.99907C2.3607 2.55907 2.16869 2.48022 1.9961 2.48022H1.3572C1.12986 2.48022 0.911825 2.38991 0.751069 2.22916C0.590312 2.06842 0.5 1.8504 0.5 1.62307ZM6.29812 9.1545L7.15647 12.2848C7.32791 12.9042 7.89137 13.3351 8.53485 13.3351H15.333C15.6436 13.3351 15.9457 13.2339 16.1936 13.0468C16.4414 12.8598 16.6216 12.5971 16.7068 12.2985L18.6921 5.33736H5.1689L6.28212 9.09393L6.29812 9.1545ZM10.215 18.4802C10.215 19.0864 9.97413 19.6678 9.54545 20.0965C9.11676 20.5251 8.53534 20.7659 7.92909 20.7659C7.32284 20.7659 6.74142 20.5251 6.31273 20.0965C5.88405 19.6678 5.64322 19.0864 5.64322 18.4802C5.64322 17.874 5.88405 17.2926 6.31273 16.864C6.74142 16.4353 7.32284 16.1945 7.92909 16.1945C8.53534 16.1945 9.11676 16.4353 9.54545 16.864C9.97413 17.2926 10.215 17.874 10.215 18.4802ZM8.50056 18.4802C8.50056 18.3287 8.44035 18.1833 8.33318 18.0762C8.22601 17.969 8.08065 17.9088 7.92909 17.9088C7.77753 17.9088 7.63217 17.969 7.525 18.0762C7.41783 18.1833 7.35762 18.3287 7.35762 18.4802C7.35762 18.6318 7.41783 18.7771 7.525 18.8843C7.63217 18.9914 7.77753 19.0516 7.92909 19.0516C8.08065 19.0516 8.22601 18.9914 8.33318 18.8843C8.44035 18.7771 8.50056 18.6318 8.50056 18.4802ZM18.2155 18.4802C18.2155 19.0864 17.9747 19.6678 17.546 20.0965C17.1173 20.5251 16.5359 20.7659 15.9296 20.7659C15.3234 20.7659 14.742 20.5251 14.3133 20.0965C13.8846 19.6678 13.6438 19.0864 13.6438 18.4802C13.6438 17.874 13.8846 17.2926 14.3133 16.864C14.742 16.4353 15.3234 16.1945 15.9296 16.1945C16.5359 16.1945 17.1173 16.4353 17.546 16.864C17.9747 17.2926 18.2155 17.874 18.2155 18.4802ZM16.5011 18.4802C16.5011 18.3287 16.4409 18.1833 16.3337 18.0762C16.2266 17.969 16.0812 17.9088 15.9296 17.9088C15.7781 17.9088 15.6327 17.969 15.5256 18.0762C15.4184 18.1833 15.3582 18.3287 15.3582 18.4802C15.3582 18.6318 15.4184 18.7771 15.5256 18.8843C15.6327 18.9914 15.7781 19.0516 15.9296 19.0516C16.0812 19.0516 16.2266 18.9914 16.3337 18.8843C16.4409 18.7771 16.5011 18.6318 16.5011 18.4802Z" fill="currentColor"/>
        </svg>
        <span x-cloak
              x-show="$store.cart && ($store.cart.qty > 0)"
              x-text="$store.cart ? $store.cart.qty : 0"
              class="absolute -top-1 right-0 bg-red-600 text-white text-[10px] leading-none rounded-full px-1 min-w-[16px] text-center">0</span>
    </a>

    {{-- backdrop: начинается ниже шапки --}}
    <div x-show="isOpen"
         x-cloak
         x-transition.opacity
         @click="close"
         class="fixed left-0 right-0 z-[40] bg-black/40"
         :style="`top:${hdr}px; height: calc(100dvh - ${hdr}px);`"></div>

    {{-- панель: тоже ниже шапки, ширины по брейкпоинтам --}}
    <div x-show="isOpen"
         x-cloak
         x-transition:enter="transition transform duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition transform duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 z-[50] bg-white shadow-xl overflow-y-auto"
         :style="`top:${hdr}px; height: calc(100dvh - ${hdr}px);`"
    >
        <div class="w-screen md:w-[768px] xl:w-[800px] h-full flex flex-col">
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-semibold">{{ st('cart.korzina', 'Кошик') }}</h2>
                <button @click="close" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div x-ref="box" class="min-h-[120px] p-2 grow">
                <div class="p-6 text-center text-gray-500">{{ st('cart.zavantazhennya', 'Завантаження') }}...</div>
            </div>
        </div>
    </div>
</div>
