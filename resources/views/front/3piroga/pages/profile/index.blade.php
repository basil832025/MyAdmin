{{-- resources/views/profile/index.blade.php --}}
@php

    $user = auth()->user();
@endphp

@extends(front_view('layouts.app'))

@section('title', __('Профіль'))

@section('content')
    <div
        x-data="profilePage()"
        class="mx-auto desk:w-[1200px] px-4 md:px-6 desk:px-0"
    >
        <div class="xl:grid xl:grid-cols-[240px,1fr] md:gap-6">
            {{-- Леве меню (desktop) --}}
            <aside class="hidden xl:block">
                @include(front_view('pages.menu.profile-menu'))
            </aside>

            {{-- Контент --}}
            <main>
                {{-- Заголовок --}}
                <h1 class="text-[28px] font-bold text-[#19191A] mb-4">
                    {{ st('profile.title','Профіль') }}
                </h1>

                {{-- Карта профілю --}}
                <div class="bg-white rounded-[6px] ring-1 ring-black/10 p-4 md:p-6">

                        {{-- Имя --}}

                        <div x-data="{ editing: false, value: @js($user->name) }"
                             class="py-4 border-b border-gray-100">

                            {{-- ======= Просмотр ======= --}}
                            <div x-show="!editing" class="flex items-start justify-between">
                                <div class="flex items-start gap-2">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M15.5 13.875C14.3789 13.875 13.8398 14.5 12 14.5C10.1602 14.5 9.625 13.875 8.5 13.875C5.60156 13.875 3.25 16.2266 3.25 19.125V20.125C3.25 21.1602 4.08984 22 5.125 22H18.875C19.9102 22 20.75 21.1602 20.75 20.125V19.125C20.75 16.2266 18.3984 13.875 15.5 13.875ZM18.875 20.125H5.125V19.125C5.125 17.2656 6.64062 15.75 8.5 15.75C9.07031 15.75 9.99609 16.375 12 16.375C14.0195 16.375 14.9258 15.75 15.5 15.75C17.3594 15.75 18.875 17.2656 18.875 19.125V20.125ZM12 13.25C15.1055 13.25 17.625 10.7305 17.625 7.625C17.625 4.51953 15.1055 2 12 2C8.89453 2 6.375 4.51953 6.375 7.625C6.375 10.7305 8.89453 13.25 12 13.25ZM12 3.875C14.0664 3.875 15.75 5.55859 15.75 7.625C15.75 9.69141 14.0664 11.375 12 11.375C9.93359 11.375 8.25 9.69141 8.25 7.625C8.25 5.55859 9.93359 3.875 12 3.875Z" fill="#929292" stroke="#929292" stroke-width="0.0390625"/>
                                    </svg>

                                    <div>
                                        <div class="text-[15px] font-semibold text-[#19191A]"> {{ st('profile.name','Імʼя') }}  </div>
                                        <div class="text-[15px] text-gray-500" x-text="value || '—'"></div>
                                    </div>
                                </div>

                                {{-- Карандаш --}}
                                <button type="button" class="p-1 text-gray-400 hover:text-gray-600"
                                        @click="editing = true">
                                    <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.8861 1.65185C18.5306 1.29633 18.1086 1.01431 17.6441 0.821898C17.1797 0.629489 16.6818 0.530457 16.1791 0.530457C15.6763 0.530457 15.1785 0.629489 14.714 0.821898C14.2496 1.01431 13.8276 1.29633 13.4721 1.65185L12.3721 2.75385C12.317 2.79776 12.2667 2.84737 12.2221 2.90185L1.30208 13.8238C1.16246 13.9631 1.06713 14.1405 1.02808 14.3338L0.0200845 19.3338C-0.0128006 19.4954 -0.00521088 19.6627 0.0421778 19.8206C0.0895665 19.9786 0.175283 20.1224 0.291694 20.2392C0.408104 20.356 0.551596 20.4422 0.709388 20.4901C0.867181 20.538 1.03438 20.5462 1.19608 20.5138L6.20408 19.5138C6.39738 19.4748 6.57482 19.3795 6.71408 19.2398L18.8861 7.06785C19.2416 6.71238 19.5236 6.29036 19.716 5.82589C19.9084 5.36142 20.0075 4.86359 20.0075 4.36085C20.0075 3.8581 19.9084 3.36028 19.716 2.89581C19.5236 2.43134 19.2416 2.00932 18.8861 1.65385M13.0041 4.95185L15.5901 7.53785L5.51408 17.6118L2.27808 18.2598L2.92808 15.0278L13.0041 4.95185ZM17.0041 6.12385L14.4181 3.53585L14.8861 3.06785C15.055 2.89473 15.2566 2.75685 15.4792 2.66222C15.7018 2.56758 15.941 2.51806 16.1829 2.51653C16.4247 2.515 16.6645 2.56149 16.8883 2.65331C17.1121 2.74512 17.3154 2.88043 17.4865 3.0514C17.6576 3.22237 17.7931 3.4256 17.8851 3.64931C17.9771 3.87302 18.0237 4.11276 18.0224 4.35464C18.0211 4.59652 17.9717 4.83573 17.8773 5.0584C17.7828 5.28107 17.6451 5.48279 17.4721 5.65185L17.0041 6.12385Z" fill="#929292"/>
                                    </svg>

                                </button>
                            </div>

                            {{-- ======= Редактирование ======= --}}
                            <div x-show="editing" class="mt-3">
                                <form method="post" action="{{ route('profile.update') }}" id="name-form-{{ $user->id }}"
                                      class="flex flex-col gap-3"
                                      @submit.prevent="
                                        const form = $el;
                                        const submitBtn = form.querySelector('button[type=\'submit\']');
                                        if (submitBtn) submitBtn.disabled = true;
                                        const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.content || '';
                                        fetch(form.action, {
                                            method: 'POST',
                                            body: new FormData(form),
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': csrfToken
                                            }
                                        })
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.success || !data.errors) {
                                                editing = false;
                                                // Обновляем значение из ответа или оставляем текущее
                                                if (data.name) value = data.name;
                                            } else {
                                                alert(data.message || 'Помилка збереження');
                                            }
                                        })
                                        .catch(err => {
                                            console.error(err);
                                            // Fallback: обычная отправка формы
                                            form.submit();
                                        })
                                        .finally(() => {
                                            if (submitBtn) submitBtn.disabled = false;
                                        });
                                      ">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="field" value="name">

                                    <div class="flex flex-nowrap items-center gap-3">
                                        <!-- Поле фиксированной ширины -->
                                        <div class="relative inline-block w-full md:w-[400px] xl:w-[450px]">
                                          <span class="absolute -top-2 left-3 px-1 bg-white text-xs text-gray-500 z-10">
                                            {{ st('profile.name','Імʼя') }} <span class="text-red-500">*</span>
                                          </span>

                                            <input type="text" name="name" x-model="value"
                                                   class="h-12 w-full pl-3 pr-28 rounded-lg ring-1 ring-black/10 bg-white outline-none text-[15px] focus:ring-black/20"
                                                   placeholder="{{ st('profile.name','Імʼя') }} ">

                                            <!-- Зберегти внутри инпута (desktop) -->
                                            <button type="submit"
                                                    class="hidden md:block absolute right-3 top-1/2 -translate-y-1/2 text-[15px] font-semibold text-[#27AE60]">
                                                {{ st('profile.save','Зберегти') }}
                                            </button>
                                        </div>

                                        <!-- Скасувати справа в одной строке (desktop) -->
                                        <button type="button"
                                                class="hidden sm:inline text-[15px]  mx-6 font-semibold text-[#19191A] whitespace-nowrap"
                                                @click="value=@js($user->name); editing=false">
                                            {{ st('profile.cancel','Скасувати') }}
                                        </button>
                                    </div>

                                    <!-- Мобилка: кнопки снизу -->
                                    <div class="flex justify-between items-center gap-4 sm:hidden">
                                        <button type="button" class="text-[15px] font-semibold text-[#19191A]"
                                                @click="value=@js($user->name); editing=false">
                                            {{ st('profile.cancel','Скасувати') }}
                                        </button>
                                        <button type="submit" class="text-[15px] font-semibold text-[#27AE60]">
                                            {{ st('profile.save','Зберегти') }}
                                        </button>
                                    </div>
                                </form>
                            </div>




                        </div>

                        {{-- ===== Телефон (readonly) ===== --}}
                        <div class="py-4 border-b border-gray-100">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-2">
                                    {{-- иконка телефона --}}
                                    <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11 16.5319C10.7348 16.5319 10.4804 16.6372 10.2929 16.8248C10.1054 17.0123 10 17.2666 10 17.5319C10 17.7971 10.1054 18.0514 10.2929 18.239C10.4804 18.4265 10.7348 18.5319 11 18.5319H13C13.2652 18.5319 13.5196 18.4265 13.7071 18.239C13.8946 18.0514 14 17.7971 14 17.5319C14 17.2666 13.8946 17.0123 13.7071 16.8248C13.5196 16.6372 13.2652 16.5319 13 16.5319H11ZM9 2.53186C8.20435 2.53186 7.44129 2.84793 6.87868 3.41054C6.31607 3.97315 6 4.73621 6 5.53186V19.5319C6 20.3275 6.31607 21.0906 6.87868 21.6532C7.44129 22.2158 8.20435 22.5319 9 22.5319H15C15.7956 22.5319 16.5587 22.2158 17.1213 21.6532C17.6839 21.0906 18 20.3275 18 19.5319V5.53186C18 4.73621 17.6839 3.97315 17.1213 3.41054C16.5587 2.84793 15.7956 2.53186 15 2.53186H9ZM8 5.53186C8 5.26664 8.10536 5.01229 8.29289 4.82475C8.48043 4.63722 8.73478 4.53186 9 4.53186H15C15.2652 4.53186 15.5196 4.63722 15.7071 4.82475C15.8946 5.01229 16 5.26664 16 5.53186V19.5319C16 19.7971 15.8946 20.0514 15.7071 20.239C15.5196 20.4265 15.2652 20.5319 15 20.5319H9C8.73478 20.5319 8.48043 20.4265 8.29289 20.239C8.10536 20.0514 8 19.7971 8 19.5319V5.53186Z" fill="#929292"/>
                                    </svg>

                                    <div>
                                        <div class="text-[15px] font-semibold text-[#19191A]">{{ st('profile.phone','Телефон') }}</div>
                                        <div class="text-[15px] text-gray-500">{{ $user->phone }}</div>
                                    </div>
                                </div>
                                {{-- карандаша нет, номер не редактируем --}}
                            </div>
                        </div>

                        {{-- Email --}}
                        {{-- ===== Email (просмотр → карандаш → редактирование) ===== --}}
                        <div x-data="{ editing: false, value: @js($user->email) }" class="py-4 border-b border-gray-100">
                            {{-- просмотр --}}
                            <div x-show="!editing" class="flex items-start justify-between">
                                <div class="flex items-start gap-2">
                                    <svg width="20" height="17" viewBox="0 0 20 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M0 2.53186C0 2.00143 0.210714 1.49272 0.585786 1.11765C0.960859 0.742574 1.46957 0.53186 2 0.53186H18C18.5304 0.53186 19.0391 0.742574 19.4142 1.11765C19.7893 1.49272 20 2.00143 20 2.53186V14.5319C20 15.0623 19.7893 15.571 19.4142 15.9461C19.0391 16.3211 18.5304 16.5319 18 16.5319H2C1.46957 16.5319 0.960859 16.3211 0.585786 15.9461C0.210714 15.571 0 15.0623 0 14.5319V2.53186ZM3.519 2.53186L10 8.20286L16.481 2.53186H3.519ZM18 3.86086L10.659 10.2849C10.4766 10.4446 10.2424 10.5327 10 10.5327C9.75755 10.5327 9.52336 10.4446 9.341 10.2849L2 3.86086V14.5319H18V3.86086Z" fill="#929292"/>
                                    </svg>

                                    <div>
                                        <div class="text-[15px] font-semibold text-[#19191A]">Email</div>
                                        <div class="text-[15px] text-gray-500" x-text="value || '—'"></div>
                                    </div>
                                </div>
                                <button type="button" class="p-1 text-gray-400 hover:text-gray-600" @click="editing=true">
                                    <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.8861 1.65185C18.5306 1.29633 18.1086 1.01431 17.6441 0.821898C17.1797 0.629489 16.6818 0.530457 16.1791 0.530457C15.6763 0.530457 15.1785 0.629489 14.714 0.821898C14.2496 1.01431 13.8276 1.29633 13.4721 1.65185L12.3721 2.75385C12.317 2.79776 12.2667 2.84737 12.2221 2.90185L1.30208 13.8238C1.16246 13.9631 1.06713 14.1405 1.02808 14.3338L0.0200845 19.3338C-0.0128006 19.4954 -0.00521088 19.6627 0.0421778 19.8206C0.0895665 19.9786 0.175283 20.1224 0.291694 20.2392C0.408104 20.356 0.551596 20.4422 0.709388 20.4901C0.867181 20.538 1.03438 20.5462 1.19608 20.5138L6.20408 19.5138C6.39738 19.4748 6.57482 19.3795 6.71408 19.2398L18.8861 7.06785C19.2416 6.71238 19.5236 6.29036 19.716 5.82589C19.9084 5.36142 20.0075 4.86359 20.0075 4.36085C20.0075 3.8581 19.9084 3.36028 19.716 2.89581C19.5236 2.43134 19.2416 2.00932 18.8861 1.65385M13.0041 4.95185L15.5901 7.53785L5.51408 17.6118L2.27808 18.2598L2.92808 15.0278L13.0041 4.95185ZM17.0041 6.12385L14.4181 3.53585L14.8861 3.06785C15.055 2.89473 15.2566 2.75685 15.4792 2.66222C15.7018 2.56758 15.941 2.51806 16.1829 2.51653C16.4247 2.515 16.6645 2.56149 16.8883 2.65331C17.1121 2.74512 17.3154 2.88043 17.4865 3.0514C17.6576 3.22237 17.7931 3.4256 17.8851 3.64931C17.9771 3.87302 18.0237 4.11276 18.0224 4.35464C18.0211 4.59652 17.9717 4.83573 17.8773 5.0584C17.7828 5.28107 17.6451 5.48279 17.4721 5.65185L17.0041 6.12385Z" fill="#929292"/>
                                    </svg>

                                </button>
                            </div>

                            {{-- редактирование --}}
                            <div x-show="editing" class="mt-3">
                                <form method="post" action="{{ route('profile.update') }}" id="email-form-{{ $user->id }}"
                                      class="flex flex-col gap-3"
                                      @submit.prevent="
                                        const form = $el;
                                        const submitBtn = form.querySelector('button[type=\'submit\']');
                                        if (submitBtn) submitBtn.disabled = true;
                                        const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.content || '';
                                        fetch(form.action, {
                                            method: 'POST',
                                            body: new FormData(form),
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': csrfToken
                                            }
                                        })
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.success || !data.errors) {
                                                editing = false;
                                                // Обновляем значение из ответа или оставляем текущее
                                                if (data.email) value = data.email;
                                            } else {
                                                alert(data.message || 'Помилка збереження');
                                            }
                                        })
                                        .catch(err => {
                                            console.error(err);
                                            // Fallback: обычная отправка формы
                                            form.submit();
                                        })
                                        .finally(() => {
                                            if (submitBtn) submitBtn.disabled = false;
                                        });
                                      ">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="field" value="email">
                                    
                                    <div class="flex flex-nowrap items-center gap-3">
                                        <div class="relative inline-block w-full md:w-[400px] xl:w-[450px]">
                                            <span class="absolute -top-2 left-3 px-1 bg-white text-xs text-gray-500">Email</span>
                                            <input type="email" name="email" x-model="value"
                                                   class="h-12 w-full pl-3 pr-28 rounded-lg ring-1 ring-black/10 bg-white outline-none text-[15px] focus:ring-black/20"
                                                   placeholder="you@example.com">
                                            {{-- desktop: сохранить внутри --}}
                                            <button type="submit"
                                                    class="hidden md:block absolute right-3 top-1/2 -translate-y-1/2 text-[15px] font-semibold text-[#27AE60]">
                                                {{ st('profile.save','Зберегти') }}
                                            </button>
                                        </div>

                                        <button type="button"
                                                class="hidden sm:inline text-[15px]  mx-6 font-semibold text-[#19191A] whitespace-nowrap"
                                                @click="value=@js($user->email); editing=false">
                                            {{ st('profile.cancel','Скасувати') }}
                                        </button>
                                    </div>
                                    
                                    {{-- mobile: кнопки снизу --}}
                                    <div class="flex justify-between items-center gap-4 md:hidden">
                                        <button type="button" class="text-[15px] font-semibold text-[#19191A]"
                                                @click="value=@js($user->email); editing=false">
                                            {{ st('profile.cancel','Скасувати') }}
                                        </button>
                                        <button type="submit" class="text-[15px] font-semibold text-[#27AE60]">
                                            {{ st('profile.save','Зберегти') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        {{-- ===== Пароль (просмотр → карандаш → редактирование) =====
                        <div x-data="{ editing:false, current:'', password:'', password_confirmation:'' }" class="py-4 border-b border-gray-100">
                            <!--  просмотр -->
                            <div x-show="!editing" class="flex items-start justify-between">
                                <div class="flex items-start gap-2">
                                    <!-- иконка замка -->
                                    <svg width="20" height="22" viewBox="0 0 20 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 8.53186V6.53186C5 3.77043 7.23858 1.53186 10 1.53186C12.7614 1.53186 15 3.77043 15 6.53186V8.53186M4 8.53186H16C17.1046 8.53186 18 9.42729 18 10.5319V18.5319C18 19.6364 17.1046 20.5319 16 20.5319H4C2.89543 20.5319 2 19.6364 2 18.5319V10.5319C2 9.42729 2.89543 8.53186 4 8.53186Z" stroke="#929292" stroke-width="2"/>
                                    </svg>
                                    <div>
                                        <div class="text-[15px] font-semibold text-[#19191A]">{{ st('profile.password','Пароль') }} </div>
                                        <div class="text-[15px] text-gray-500">••••••••</div>
                                    </div>
                                </div>
                                <button type="button" class="p-1 text-gray-400 hover:text-gray-600" @click="editing = true">
                                    <!-- карандаш -->
                                    <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.8861 1.65185C18.5306 1.29633 18.1086 1.01431 17.6441 0.821898C17.1797 0.629489 16.6818 0.530457 16.1791 0.530457C15.6763 0.530457 15.1785 0.629489 14.714 0.821898C14.2496 1.01431 13.8276 1.29633 13.4721 1.65185L12.3721 2.75385C12.317 2.79776 12.2667 2.84737 12.2221 2.90185L1.30208 13.8238C1.16246 13.9631 1.06713 14.1405 1.02808 14.3338L0.0200845 19.3338C-0.0128006 19.4954 -0.00521088 19.6627 0.0421778 19.8206C0.0895665 19.9786 0.175283 20.1224 0.291694 20.2392C0.408104 20.356 0.551596 20.4422 0.709388 20.4901C0.867181 20.538 1.03438 20.5462 1.19608 20.5138L6.20408 19.5138C6.39738 19.4748 6.57482 19.3795 6.71408 19.2398L18.8861 7.06785C19.2416 6.71238 19.5236 6.29036 19.716 5.82589C19.9084 5.36142 20.0075 4.86359 20.0075 4.36085C20.0075 3.8581 19.9084 3.36028 19.716 2.89581C19.5236 2.43134 19.2416 2.00932 18.8861 1.65385M13.0041 4.95185L15.5901 7.53785L5.51408 17.6118L2.27808 18.2598L2.92808 15.0278L13.0041 4.95185ZM17.0041 6.12385L14.4181 3.53585L14.8861 3.06785C15.055 2.89473 15.2566 2.75685 15.4792 2.66222C15.7018 2.56758 15.941 2.51806 16.1829 2.51653C16.4247 2.515 16.6645 2.56149 16.8883 2.65331C17.1121 2.74512 17.3154 2.88043 17.4865 3.0514C17.6576 3.22237 17.7931 3.4256 17.8851 3.64931C17.9771 3.87302 18.0237 4.11276 18.0224 4.35464C18.0211 4.59652 17.9717 4.83573 17.8773 5.0584C17.7828 5.28107 17.6451 5.48279 17.4721 5.65185L17.0041 6.12385Z" fill="#929292"/>
                                    </svg>
                                </button>
                            </div>

                        <!-- редактирование -->
                            <div x-show="editing" class="mt-3">
                                <!-- ОДНА локальная форма, без form="..." привязок, без абсолютных submit-кнопок -->
                                <form id="password-form" method="POST" action="{{ route('profile.update') }}"
                                      @submit="$el.dataset.s='go';"
                                      class="w-full max-w-[450px]">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="field" value="password">
                                    <div class="flex flex-nowrap items-center gap-3">

                                        <div class="relative inline-block w-full md:w-[400px] xl:w-[450px]">
                                    <label class="block relative">
                <span class="absolute -top-2 left-3 px-1 bg-white text-xs text-gray-500 z-10">
                 {{ st('profile.new_password','Новий пароль') }} <span class="text-red-500">*</span>
                </span>
                                        <input
                                            type="password"
                                            name="password"
                                            minlength="6"
                                            required
                                            class="h-12 w-full pl-3 pr-28 rounded-lg ring-1 ring-black/10 bg-white outline-none text-[15px] focus:ring-black/20"
                                            placeholder="{{ st('profile.min_6_chars','Мінімум 6 символів') }}"
                                        >
                                    </label>
                                    @error('password')
                                    <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p>
                                    @enderror
                                    <!-- Кнопки под инпутом: так 100% уходит submit -->

                                        <button type="submit"
                                                class="hidden md:block absolute right-3 top-1/2 -translate-y-1/2 text-[15px] font-semibold text-[#27AE60]">
                                            {{ st('profile.save','Зберегти') }}
                                        </button>

                                    </div>

                                        <button type="button"   class="hidden sm:inline text-[15px]  mx-6 font-semibold text-[#19191A] whitespace-nowrap"
                                                @click="editing=false">
                                            {{ st('profile.cancel','Скасувати') }}
                                        </button>
                                    </div>
                                    <!-- mobile: кнопки снизу -->
                                    <div class="flex justify-between items-center gap-4 mt-3 md:hidden">
                                        <button type="button" class="text-[15px] font-semibold text-[#19191A]"
                                                @click="value=@js($user->email); editing=false">
                                            {{ st('profile.cancel','Скасувати') }}
                                        </button>
                                        <button type="submit" class="text-[15px] font-semibold text-[#27AE60]">
                                            {{ st('profile.save','Зберегти') }}
                                        </button>

                                    </div>
                                </form>
                            </div>
                        </div>
                    --}}


                    {{-- ===== Дата рождения ===== --}}

                    @php
                        $birthdaySet = !empty($user->birthday);
                        $birthdayVal = $birthdaySet ? $user->birthday->format('d.m.Y') : '';
                        $loc = app()->getLocale();
                        $fpLocaleKey = in_array($loc, ['uk','ru','en']) ? $loc : 'en';
                    @endphp

                    <div
                        x-data="{
        fp: null,
        readonly: {{ $birthdaySet ? 'true' : 'false' }},
        confirmOpen: false,
        selectedDate: '',
        formatBirthdayMask(raw) {
            const digits = String(raw ?? '').replace(/\D/g, '').slice(0, 8);
            if (digits.length <= 2) return digits;
            if (digits.length <= 4) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
            return `${digits.slice(0, 2)}.${digits.slice(2, 4)}.${digits.slice(4)}`;
        },
        onBirthdayInput(e) {
            const el = e?.target;
            if (!el) return;
            el.value = this.formatBirthdayMask(el.value);
        },
        normalizeBirthdayInput(raw) {
            const source = String(raw ?? '').trim();
            if (!source) return '';

            const digits = source.replace(/\D/g, '');
            if (digits.length === 8) {
                return `${digits.slice(0, 2)}.${digits.slice(2, 4)}.${digits.slice(4, 8)}`;
            }

            const normalized = source
                .replace(/[\/,\-]+/g, '.')
                .replace(/\s+/g, '')
                .replace(/\.+/g, '.')
                .replace(/^\.|\.$/g, '');

            const m = normalized.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/);
            if (!m) return '';

            const day = m[1].padStart(2, '0');
            const month = m[2].padStart(2, '0');
            const year = m[3];
            return `${day}.${month}.${year}`;
        },
        submitForm() {
            const f = this.$refs.form;
            if (!f) return;
            if (typeof f.requestSubmit === 'function') f.requestSubmit(); else f.submit();
        },
        openConfirm(str) {
            if (this.readonly) return;
            if (!str) str = this.$refs.birthday.value;

            const normalized = this.normalizeBirthdayInput(str);
            if (!normalized) return;

            this.$refs.birthday.value = normalized;
            this.selectedDate = normalized;
            this.confirmOpen = true;
        },
        init() {
            const key = '{{ $fpLocaleKey }}' === 'ua' ? 'uk' : '{{ $fpLocaleKey }}';
            const l10n = key === 'ru' ? flatpickr.l10ns.ru
                        : key === 'uk' ? flatpickr.l10ns.uk
                        : flatpickr.l10ns.default;

            if (!this.readonly && window.flatpickr) {
                this.fp = flatpickr(this.$refs.birthday, {
                    dateFormat: 'd.m.Y',
                    allowInput: true,
                    maxDate: 'today',
                    disableMobile: true,
                    locale: l10n,
                    // ← выбор из календаря тоже открывает подтверждение
                    onChange: (_dates, str) => this.openConfirm(str),
                });
            }
        },
        openCal(){ if (!this.readonly && this.fp) this.fp.open(); },
        confirmSave(){ this.confirmOpen = false; this.submitForm(); }
    }"
                        x-init="init()"
                    >
                        <form method="post" action="{{ route('profile.update') }}" x-ref="form" class="max-w-[450px]">
                            @csrf @method('PUT')
                            <input type="hidden" name="field" value="birthday">

                            <div class="relative">
                                <input
                                    x-ref="birthday"
                                    name="birthday"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    minlength="10" maxlength="10"
                                    placeholder="{{ st('profile.date_format_ddmmyyyy','ДД.ММ.ГГГГ') }}"
                                    value="{{ old('birthday', $birthdayVal) }}"
                                    @if($birthdaySet) readonly @endif
                                    @input="onBirthdayInput($event)"
                                    @keyup.enter.prevent="openConfirm()"
                                    @blur="if(($el.value || '').trim() !== '') openConfirm()"
                                    class="h-11 w-full pl-3 pr-10 rounded-lg ring-1 ring-black/10 bg-white outline-none text-[15px]
                       placeholder:text-gray-400 {{ $birthdaySet ? 'bg-gray-50 text-gray-500' : '' }}">
                                <button type="button" @click.prevent="openCal()" :disabled="readonly"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#FF7500] disabled:opacity-40">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none"
                                         viewBox="0 0 24 24" stroke="#FF7500" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/>
                                        <path d="M16 2v4M8 2v4M2 10h20"/>
                                    </svg>
                                </button>
                            </div>

                            @error('birthday')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </form>

                        <!-- Модалка подтверждения -->
                        <template x-if="confirmOpen">
                            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @keydown.escape.window="confirmOpen=false">
                                <div class="bg-white rounded-2xl p-6 shadow-lg w-[90%] max-w-[380px]">
                                    <h3 class="text-lg font-semibold mb-3">{{ st('profile.birthdate_confirmation','Підтвердження дати народження') }}</h3>
                                    <p class="text-sm text-gray-600 mb-4">
                                        {{ st('profile.you_selected','Ви обрали') }}<span class="font-medium" x-text="selectedDate"></span>.<br>
                                        {{ st('profile.birthdate_change_confirm','Після підтвердження змінити дату народження буде неможливо. Ви впевнені?') }}
                                    </p>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" class="px-4 py-2 text-sm rounded-lg bg-gray-200 hover:bg-gray-300"
                                                @click="confirmOpen=false"> {{ st('profile.cancel','Скасувати') }} </button>
                                        <button type="button" class="px-4 py-2 text-sm rounded-lg bg-[#FF7500] text-white hover:bg-orange-600"
                                                @click="confirmSave()">{{ st('profile.confirm','Підтвердити') }} </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- ===== Аватар + кнопка ===== --}}
                        <div class="py-6">
                            <div class="flex items-center gap-4">
                                <div class="w-[72px] h-[72px] rounded-full ring-1 ring-black/10 overflow-hidden bg-gray-100">
                                    @if($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" class="w-[80px] h-[80px] object-cover" alt="">
                                    @else
                                        <div class="w-20 h-20 rounded-full bg-neutral-200 flex items-center justify-center">
                                             <svg width="80" height="80" viewBox="0 0 80 80" fill="none"
                                                 xmlns="http://www.w3.org/2000/svg">
                                                <rect x="0" y="0" width="80" height="80" rx="40" fill="#E5E7EB"/>
                                                <g transform="translate(-0.5,0)"> <!-- смещение содержимого на 0.5px влево -->
                                                    <path d="M40.5 48.2181C42.375 48.2181 43.969 47.5621 45.282 46.2501C46.595 44.9381 47.251 43.3441 47.25 41.4681C47.249 39.5921 46.593 37.9986 45.282 36.6876C43.971 35.3766 42.377 34.7201 40.5 34.7181C38.623 34.7161 37.0295 35.3726 35.7195 36.6876C34.4095 38.0026 33.753 39.5961 33.75 41.4681C33.747 43.3401 34.4035 44.9341 35.7195 46.2501C37.0355 47.5661 38.629 48.2221 40.5 48.2181ZM40.5 45.2181C39.45 45.2181 38.5625 44.8556 37.8375 44.1306C37.1125 43.4056 36.75 42.5181 36.75 41.4681C36.75 40.4181 37.1125 39.5306 37.8375 38.8056C38.5625 38.0806 39.45 37.7181 40.5 37.7181C41.55 37.7181 42.4375 38.0806 43.1625 38.8056C43.8875 39.5306 44.25 40.4181 44.25 41.4681C44.25 42.5181 43.8875 43.4056 43.1625 44.1306C42.4375 44.8556 41.55 45.2181 40.5 45.2181ZM28.5 53.4681C27.675 53.4681 26.969 53.1746 26.382 52.5876C25.795 52.0006 25.501 51.2941 25.5 50.4681V32.4681C25.5 31.6431 25.794 30.9371 26.382 30.3501C26.97 29.7631 27.676 29.4691 28.5 29.4681H33.225L36 26.4681H45L47.775 29.4681H52.5C53.325 29.4681 54.0315 29.7621 54.6195 30.3501C55.2075 30.9381 55.501 31.6441 55.5 32.4681V50.4681C55.5 51.2931 55.2065 51.9996 54.6195 52.5876C54.0325 53.1756 53.326 53.4691 52.5 53.4681H28.5ZM28.5 50.4681H52.5V32.4681H46.425L43.6875 29.4681H37.3125L34.575 32.4681H28.5V50.4681Z" fill="#A9A9A9"/>
                                                </g>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="photo-form-{{ $user->id }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="field" value="photo">
                                    <label class="inline-flex">
                                        <input type="file" name="photo" class="hidden" accept="image/*" 
                                               onchange="const form = document.getElementById('photo-form-{{ $user->id }}'); if(form) form.submit();">
                                        <span class="inline-flex h-11 items-center px-5 text-[15px] rounded-[10px] ring-1 ring-black/10 bg-white shadow-sm cursor-pointer">
                       {{ st('profile.add_photo','Додати фото') }}
                </span>
                                    </label>
                                </form>
                            </div>
                        </div>

                        {{-- На мобільному отдельной кнопки «Зберегти» не нужно — поля сохраняются по своим действиям.
                             Если хочеш загальну — раскоментируй: --}}
                        {{-- <div class="pt-2 md:hidden">
                            <button class="w-full h-10 rounded bg-[#27AE60] text-white text-sm">{{ __('Зберегти всі зміни') }}</button>
                        </div> --}}

                </div>

                {{-- Футер страницы можно оставить общий --}}
            </main>
        </div>
    </div>

    <script>
        function profilePage(){
            return {
                editing: { name: false, email: false },
                state: {
                    name: @json(old('name', $user->name)),
                    email: @json(old('email', $user->email)),
                },
                resetField(field, original){
                    this.state[field] = original || '';
                    this.editing[field] = false;
                },
            }
        }
    </script>
@endsection
