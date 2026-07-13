@extends(front_view('layouts.app'))

@section('title', $product->display_name)

@section('content')
   
    <div class="grid md:grid-cols-2 gap-8">
        <div>
            <img src="{{ $product->image_url }}" alt="{{ $product->display_name }}"
                 class="w-full rounded-2xl bg-gray-100 object-cover">
        </div>
        <div>
            <h1 class="text-2xl font-bold">{{ $product->display_name }}</h1>

            <div class="mt-4 text-2xl font-semibold">
                {{ number_format($product->unit_price, 0, ',', ' ') }} грн
            </div>

            <div class="mt-6">
                @php $desc = data_get($product->description, app()->getLocale()) @endphp
                @if($desc)
                    <div class="prose max-w-none">{{ $desc }}</div>
                @endif
            </div>

            <div class="mt-6">
                <button class="h-11 px-6 rounded-xl bg-orange-500 text-white hover:bg-orange-600">
                    Додати в кошик
                </button>
            </div>
        </div>
    </div>
@endsection
