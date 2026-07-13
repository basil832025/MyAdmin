<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Shop\ClientAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientAddressController extends Controller
{
    private function localizedRoute(string $baseName, array $params = []): string
    {
        $locale = app()->getLocale();

        if (in_array($locale, ['ru', 'en'], true)) {
            return route('localized.' . $baseName, array_merge(['locale' => $locale], $params));
        }

        return route($baseName, $params);
    }

    private function checkOwner(ClientAddress $address): void
    {
        $user = Auth::user();

        if (! $user || (int) $address->client_id !== (int) $user->id) {
            abort(403);
        }
    }

    public function index()
    {
        $client = Auth::user();

        $addresses = $client->addresses()
            ->orderByDesc('id')
            ->get();

        return view(front_view('pages.profile.addresses.index'), [
            'addresses' => $addresses,
        ]);
    }

    public function create()
    {
        return view(front_view('pages.profile.addresses.form'), [
            'address' => new ClientAddress(),
        ]);
    }

    public function store(Request $request)
    {
        $client = Auth::user();

        $validated = $this->validateAddress($request);

        $validated['client_id'] = $client->id;
        $validated['is_private_house'] = $request->boolean('is_private_house', false);

        ClientAddress::create($validated);

        return redirect()
            ->to($this->localizedRoute('profile.addresses.index'))
            ->with('success', st('profile.addresses.success_added', 'Адреса успішно додана'));
    }

    private function findUserAddress(Request $request): ClientAddress
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $addressId = $request->route('address');

        $address = ClientAddress::where('id', $addressId)
            ->where('client_id', $user->id)
            ->first();

        if (! $address) {
            abort(403);
        }

        return $address;
    }

    public function edit(Request $request)
    {
        $address = $this->findUserAddress($request);

        return view(front_view('pages.profile.addresses.form'), [
            'address' => $address,
        ]);
    }

    public function update(Request $request)
    {
        $address = $this->findUserAddress($request);

        $validated = $this->validateAddress($request);
        $validated['is_private_house'] = $request->boolean('is_private_house', false);

        $address->update($validated);

        return redirect()
            ->to($this->localizedRoute('profile.addresses.index'))
            ->with('success', st('profile.addresses.success_updated', 'Адреса успішно оновлена'));
    }

    public function updateCoords(Request $request)
    {
        $address = $this->findUserAddress($request);

        $validated = $request->validate([
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
            'street_place_id'   => 'nullable|string|max:255',
            'formatted_address' => 'nullable|string|max:255',
        ]);

        $address->update($validated);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $address = $this->findUserAddress($request);

        $address->delete();

        return redirect()
            ->to($this->localizedRoute('profile.addresses.index'))
            ->with('success', st('profile.addresses.success_deleted', 'Адреса успішно видалена'));
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'city'              => 'nullable|string|max:255',
            'street'            => 'required|string|max:255',
            'house'             => 'required|string|max:50',
            'apartment'         => 'nullable|string|max:50',
            'intercom'          => 'nullable|string|max:255',
            'floor'             => 'nullable|integer',
            'entrance'          => 'nullable|string|max:255',
            'note'              => 'nullable|string|max:500',
            'is_private_house'  => 'boolean',
            'type'              => 'nullable|string|max:50',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',
            'street_place_id'   => 'nullable|string|max:255',
            'formatted_address' => 'nullable|string|max:255',
        ]);
    }
}
