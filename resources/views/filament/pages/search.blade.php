@extends(front_view('layouts.app'))

@section('content')
    <div class="mx-auto max-w-5xl p-6">
        <h1 class="text-2xl font-bold mb-4">Результати пошуку</h1>
        <p>Запит: <strong>{{ $q }}</strong></p>
        {{-- @foreach($products as $p) ... @endforeach --}}
    </div>
@endsection
