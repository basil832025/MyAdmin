@extends(front_view('layouts.app'))

@section('title', st('profile.addresses.title', 'Адреса доставки'))

@section('content')
    @php
        $locale = app()->getLocale();
        $isLocalized = in_array($locale, ['ru', 'en'], true);
        $editRoute = $isLocalized ? 'localized.profile.addresses.edit' : 'profile.addresses.edit';
        $destroyRoute = $isLocalized ? 'localized.profile.addresses.destroy' : 'profile.addresses.destroy';
        $createRoute = $isLocalized ? 'localized.profile.addresses.create' : 'profile.addresses.create';
    @endphp
    <div class="mx-auto desk:w-[1200px] px-4 md:px-6 desk:px-0">
        <div class="xl:grid xl:grid-cols-[240px,1fr] md:gap-6">
            {{-- Левое меню (desktop) --}}
            <aside class="hidden xl:block">
                @include(front_view('pages.menu.profile-menu'))
            </aside>

            {{-- Контент --}}
            <main>
                {{-- Заголовок --}}
                <h1 class="text-[28px] font-bold text-[#19191A] mb-4">
                    {{ st('profile.addresses.title', 'Адреса доставки') }}
                </h1>

                {{-- Синяя пунктирная линия сверху --}}
                <div class="border-t-2 border-dashed border-blue-300 mb-6"></div>

                {{-- Сообщение об успехе --}}
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-50 text-green-800 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Список адресов --}}
                @if($addresses->count() > 0)
                    <div class="space-y-4 mb-6">
                        @foreach($addresses as $address)
                            @php
                                // Формируем полную строку адреса
                                $addressParts = [];
                                if (!empty($address->city)) {
                                    $addressParts[] = $address->city;
                                }
                                if (!empty($address->street)) {
                                    $addressParts[] = st('address.parts.street_prefix', 'вулиця').' '.$address->street;
                                }
                                if (!empty($address->house)) {
                                    $addressParts[] = st('address.parts.house_short', 'д.').$address->house;
                                }
                                if (!empty($address->apartment)) {
                                    $addressParts[] = st('address.parts.apartment_short', 'кв. ').$address->apartment;
                                }
                                $fullAddress = implode(', ', $addressParts);

                                // Тип адреса
                                $typeLabel = null;
                                $typeLabelUpper = null;
                                if (!empty($address->type)) {
                                    $map = [
                                        'home'    => ['label' => st('address.type.home', 'Дім'), 'upper' => 'HOME'],
                                        'work'    => ['label' => st('address.type.work', 'Робота'), 'upper' => 'WORK'],
                                        'friends' => ['label' => st('address.type.friends', 'Друзі'), 'upper' => 'FRIENDS'],
                                    ];
                                    $typeInfo = $map[$address->type] ?? ['label' => $address->type, 'upper' => strtoupper($address->type)];
                                    $typeLabel = $typeInfo['label'];
                                    $typeLabelUpper = $typeInfo['upper'];
                                }

                                if ($fullAddress && $typeLabelUpper) {
                                    $fullAddress .= ' ('.$typeLabelUpper.')';
                                }
                            @endphp

                             <div class="bg-white rounded-xl border border-gray-200 p-6 px-4"
                                  style="box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.08);">
                                 <div class="flex items-start justify-between">
                                     <div class="flex-1">
                                         {{-- Заголовок с типом адреса (жирный) --}}
                                         @if($typeLabel)
                                             <h3 class="text-3xl font-bold text-[#19191A] mb-2">
                                                 {{ $typeLabel }}
                                             </h3>
                                         @endif

                                         {{-- Адрес --}}
                                         <p class="text-base text-[#19191A]">
                                             {{ $fullAddress }}
                                         </p>
                                     </div>

                                    {{-- Иконки редактирования и удаления --}}
                                    <div class="flex items-center gap-3 ml-4">
                                        @php
                                            $editUrl = $isLocalized
                                                ? route($editRoute, ['locale' => $locale, 'address' => $address])
                                                : route($editRoute, ['address' => $address]);
                                            $destroyUrl = $isLocalized
                                                ? route($destroyRoute, ['locale' => $locale, 'address' => $address])
                                                : route($destroyRoute, ['address' => $address]);
                                        @endphp
                                        <a href="{{ $editUrl }}"
                                           class="p-2 text-gray-400 hover:text-[#19191A] transition"
                                           title="{{ st('profile.addresses.edit', 'Редагувати') }}">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M20.8861 3.11999C20.5306 2.76447 20.1086 2.48245 19.6441 2.29004C19.1797 2.09763 18.6818 1.9986 18.1791 1.9986C17.6763 1.9986 17.1785 2.09763 16.714 2.29004C16.2496 2.48245 15.8276 2.76447 15.4721 3.11999L14.3721 4.22199C14.317 4.2659 14.2667 4.31551 14.2221 4.36999L3.30208 15.292C3.16246 15.4313 3.06713 15.6087 3.02808 15.802L2.02008 20.802C1.9872 20.9636 1.99479 21.1308 2.04218 21.2888C2.08957 21.4467 2.17528 21.5905 2.29169 21.7073C2.4081 21.8241 2.5516 21.9103 2.70939 21.9582C2.86718 22.0062 3.03438 22.0143 3.19608 21.982L8.20408 20.982C8.39738 20.9429 8.57482 20.8476 8.71408 20.708L20.8861 8.53599C21.2416 8.18052 21.5236 7.7585 21.716 7.29403C21.9084 6.82956 22.0075 6.33173 22.0075 5.82899C22.0075 5.32624 21.9084 4.82842 21.716 4.36395C21.5236 3.89948 21.2416 3.47746 20.8861 3.12199M15.0041 6.41999L17.5901 9.00599L7.51408 19.08L4.27808 19.728L4.92808 16.496L15.0041 6.41999ZM19.0041 7.59199L16.4181 5.00399L16.8861 4.53599C17.055 4.36287 17.2566 4.22499 17.4792 4.13036C17.7018 4.03572 17.941 3.9862 18.1829 3.98467C18.4247 3.98314 18.6645 4.02963 18.8883 4.12145C19.1121 4.21326 19.3154 4.34857 19.4865 4.51954C19.6576 4.69051 19.7931 4.89374 19.8851 5.11745C19.9771 5.34116 20.0237 5.5809 20.0224 5.82278C20.0211 6.06466 19.9717 6.30387 19.8773 6.52654C19.7828 6.74921 19.6451 6.95093 19.4721 7.11999L19.0041 7.59199Z" fill="#929292"/>
                                            </svg>

                                        </a>
                                        <form action="{{ $destroyUrl }}" method="POST"
                                              onsubmit="return confirm('{{ st('profile.addresses.delete_confirm', 'Ви впевнені, що хочете видалити цю адресу?') }}');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 text-gray-400 hover:text-red-600 transition"
                                                    title="{{ st('profile.addresses.delete', 'Видалити') }}">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10.9548 0L13.3236 0.006C14.2572 0.1188 15.0756 0.558 15.75 1.2996C16.3008 1.9044 16.59 2.6076 16.6068 3.3732H23.1768C23.3962 3.37447 23.6061 3.46276 23.7605 3.61868C23.9148 3.7746 24.001 3.98541 24 4.2048C24.0006 4.42399 23.9143 4.63448 23.76 4.79014C23.6057 4.94581 23.396 5.03393 23.1768 5.0352L20.9736 5.034V18.9864C20.9736 22.038 19.8348 24 17.3244 24H6.5028C3.9924 24 2.8716 22.0488 2.8716 18.9864V5.034H0.8232C0.604434 5.03273 0.39506 4.94494 0.240816 4.78979C0.0865726 4.63465 -3.67595e-06 4.42477 1.17061e-10 4.206C1.17061e-10 3.7464 0.3684 3.3756 0.8232 3.3756H7.3872C7.404 2.7588 7.6332 2.1336 8.052 1.5156C8.676 0.594 9.6504 0.0888 10.9548 0ZM19.3272 5.034H4.5168V18.9864C4.5168 21.252 5.1408 22.3392 6.5028 22.3392H17.3244C18.6912 22.3392 19.3284 21.2424 19.3284 18.9864L19.3272 5.034ZM8.0592 7.608C8.5128 7.608 8.8812 7.98 8.8812 8.4384V18.0984C8.88184 18.3176 8.79554 18.5281 8.64123 18.6837C8.48692 18.8394 8.27718 18.9275 8.058 18.9288C7.83903 18.9272 7.6296 18.839 7.47554 18.6833C7.32149 18.5277 7.23536 18.3174 7.236 18.0984V8.4384C7.236 7.98 7.6056 7.608 8.0592 7.608ZM11.3292 7.608C11.7852 7.608 12.1524 7.98 12.1524 8.4384V18.0984C12.153 18.3176 12.0667 18.5281 11.9124 18.6837C11.7581 18.8394 11.5484 18.9275 11.3292 18.9288C11.1102 18.9272 10.9008 18.839 10.7467 18.6833C10.5927 18.5277 10.5066 18.3174 10.5072 18.0984V8.4384C10.5072 7.98 10.8756 7.608 11.3292 7.608ZM14.6028 7.608C15.0564 7.608 15.4248 7.98 15.4248 8.4384V18.0984C15.4254 18.3174 15.3393 18.5277 15.1853 18.6833C15.0312 18.839 14.8218 18.9272 14.6028 18.9288C14.3836 18.9275 14.1739 18.8394 14.0196 18.6837C13.8653 18.5281 13.779 18.3176 13.7796 18.0984V8.4384C13.779 8.21921 13.8653 8.00872 14.0196 7.85306C14.1739 7.6974 14.3836 7.60927 14.6028 7.608ZM11.0112 1.6584C10.2408 1.7124 9.7332 1.9752 9.4104 2.4528C9.1728 2.802 9.054 3.1044 9.0348 3.3744L14.9604 3.3732C14.9436 3.024 14.8056 2.7168 14.538 2.4228C14.1228 1.9668 13.662 1.7196 13.2264 1.6608L11.0112 1.6584Z" fill="#929292"/>
                                                </svg>

                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mb-6">
                        <p class="text-gray-500 mb-4">{{ st('profile.addresses.empty', 'У вас немає збережених адресів') }}</p>
                    </div>
                @endif

                {{-- Кнопка добавления нового адреса --}}
                <a href="{{ route($createRoute, $isLocalized ? ['locale' => $locale] : []) }}"
                   class="inline-flex items-center gap-2 text-[#FF7500] hover:text-orange-600 transition font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ st('profile.addresses.add', 'Додати новий адрес') }}
                </a>

                {{-- Синяя пунктирная линия снизу --}}
                <div class="border-t-2 border-dashed border-blue-300 mt-6"></div>
            </main>
        </div>
    </div>
@endsection
