@props([
'productId' => null,
'productKey' => null,
'active'    => false,
'color'     => '#FF7500',
'postUrl'   => route('favorite.toggle', $productId),
])

<button
    x-data="{
        active: @js((bool) $active),
        loading: false,
        async toggle() {
            if (this.loading) return;
            this.loading = true;

            try {
                const csrf = typeof window.ensureCsrfToken === 'function'
                    ? await window.ensureCsrfToken()
                    : (document.querySelector('meta[name=csrf-token]')?.content || '');
                const res = await fetch(@js($postUrl), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();
                if (data.status === 'added') this.active = true;
                else if (data.status === 'removed') this.active = false;

                if (data.status === 'added') {
                    window.eSputnikTrackAddToWishlist({
                        product_key: @js($productKey ?: $productId),
                        price: data.item?.price ?? null,
                        isInStock: 1,
                    });
                }
                
                // Отправляем событие для обновления счетчика в хедере
                window.dispatchEvent(new CustomEvent('favorite-updated', { 
                    detail: { qty: data.qty } 
                }));
            } catch (e) {
                console.error('Favorite toggle failed', e);
            } finally {
                this.loading = false;
            }
        }
    }"
    @click.prevent="toggle"
    :aria-pressed="String(active)"
    type="button"
    class="inline-flex h-8 w-8 items-center justify-center rounded hover:bg-black/5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FF7500]/40"
    title="В обрані"
>
    <span class="sr-only">Додати в обрані</span>

    {{-- Сердце контурное --}}
    <svg x-show="!active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 25" width="28" height="25">
        <path
            d="M14.1334 20.9384L14.0001 21.0717L13.8534 20.9384C7.52008 15.1917 3.33341 11.3917 3.33341 7.53841C3.33341
            4.87175 5.33341 2.87174 8.00008 2.87174C10.0534 2.87174 12.0534 4.20508 12.7601 6.01841H15.2401C15.9467 4.20508 17.9467
            2.87174 20.0001 2.87174C22.6667 2.87174 24.6667 4.87175 24.6667 7.53841C24.6667 11.3917 20.4801 15.1917 14.1334 20.9384ZM20.0001
            0.205078C17.6801 0.205078 15.4534 1.28508 14.0001 2.97841C12.5467 1.28508 10.3201 0.205078 8.00008 0.205078C3.89341 0.205078
            0.666748 3.41841 0.666748 7.53841C0.666748 12.5651 5.20008 16.6851 12.0667 22.9117L14.0001 24.6717L15.9334 22.9117C22.8001
            16.6851 27.3334 12.5651 27.3334 7.53841C27.3334 3.41841 24.1067 0.205078 20.0001 0.205078Z"
            fill="{{ $color }}" />
    </svg>

    {{-- Сердце залитое --}}
    <svg x-show="active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 25" width="28" height="25">
        <path
            d="M14 24.4667L12.0666 22.7067C5.19996 16.48 0.666626 12.36 0.666626 7.33333C0.666626 3.21333 3.89329 0 7.99996 0C10.32 0 12.5466 1.08 14 2.77333C15.4533 1.08 17.68 0 20 0C24.1066 0 27.3333 3.21333 27.3333 7.33333C27.3333 12.36 22.8 16.48 15.9333 22.7067L14 24.4667Z"
            fill="{{ $color }}" />
    </svg>
</button>
