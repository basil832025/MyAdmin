<?php
// app/Http/Controllers/Auth/ClientAuthController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Shop\Client;
use App\Services\Sms\EsputnikSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;



class ClientAuthController extends Controller
{
    protected function guard() { return Auth::guard('web'); }

    /**
     * Показать страницу авторизации (телефон + SMS)
     */
    public function show(Request $request)
    {
        // Если пользователь уже авторизован, редиректим
        if (Auth::check()) {
            return redirect($this->getRedirectUrl($request));
        }

        // Если есть параметр redirect_to_checkout, сохраняем URL checkout в сессию
        if ($request->has('redirect_to_checkout')) {
            $locale = app()->getLocale();
            $checkoutUrl = in_array($locale, ['ru', 'en'], true)
                ? route('localized.checkout', ['locale' => $locale])
                : route('checkout');
            $request->session()->put('auth.redirect_to_checkout', $checkoutUrl);
        }

        return view(front_view('auth.phone-sms'));
    }

    /**
     * Получить URL для редиректа после авторизации
     * Если пользователь был на checkout - возвращаем туда, иначе - профиль
     */
    protected function getRedirectUrl(Request $request): string
    {
        $checkoutUrl = $request->session()->pull('auth.redirect_to_checkout');
        
        // Логируем для отладки
        \Log::info('getRedirectUrl called', [
            'checkoutUrl' => $checkoutUrl,
            'session_id' => $request->session()->getId(),
            'all_session_keys' => array_keys($request->session()->all()),
        ]);
        
        if ($checkoutUrl && str_contains($checkoutUrl, '/checkout')) {
            return $checkoutUrl;
        }

        $locale = app()->getLocale();
        return in_array($locale, ['ru', 'en'], true)
            ? route('localized.profile.index', ['locale' => $locale])
            : route('profile.index');
    }

    // === Socialite ===
    public function redirect(string $provider)
    {
        abort_unless($this->isSocialiteProviderConfigured($provider), 404);

        $scopes = $provider === 'facebook' ? ['email'] : [];
        return Socialite::driver($provider)->scopes($scopes)->redirect();
    }

    public function callback(string $provider, Request $request)
    {
        abort_unless($this->isSocialiteProviderConfigured($provider), 404);

        $social = Socialite::driver($provider)->user();

        $providerId = (string) $social->getId();
        $email      = $social->getEmail();
        $name       = $social->getName() ?: $social->getNickname();

        $client = Client::query()
            ->where('provider_name',$provider)
            ->where('provider_id',$providerId)
            ->first();

        if (!$client && $email) {
            $client = Client::where('email',$email)->first();
        }

        if (!$client) {
            $client = new Client();
            $client->email = $email; // може бути null
        }

        if (!$client->name) $client->name = $name ?: 'Клієнт';
        $client->provider_name = $provider;
        $client->provider_id   = $providerId;

        if ($client->email && !$client->email_verified_at) {
            $client->email_verified_at = now();
        }

        if (empty($client->password)) {
            $client->password = Hash::make(Str::random(24));
        }

        $client->save();

        $this->guard()->login($client, true);
        $request->session()->regenerate();

        $redirectUrl = $this->getRedirectUrl($request);
        return redirect($redirectUrl);
    }

    private function isSocialiteProviderConfigured(string $provider): bool
    {
        if (! in_array($provider, ['google', 'facebook'], true)) {
            return false;
        }

        $config = (array) config("services.{$provider}", []);

        return filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect'] ?? null);
    }

    // === Реєстрація з підтвердженням телефону ===
    public function register(Request $r, EsputnikSms $sms)
    {
        // 1) нормализуем
        $digits = $this->normalizePhone((string)$r->input('phone'));
        $r->merge(['phone' => $digits]);

        // 2) валидация
        $data = $r->validate([
            'name'     => ['required','string','max:255'],
            'phone'    => ['required','regex:/^380\d{9}$/'],
            'email'    => ['nullable','email','max:255'],
            'password' => ['required','string','min:6','max:100','confirmed'],
        ]);

        // 3) проверка дубля (учесть старые форматы)
        $candidates = array_unique([
            $digits,                       // 380XXXXXXXXX
            '0'.substr($digits,3),         // 0XXXXXXXXX
            substr($digits,3),             // XXXXXXXXX
        ]);

        /** @var \App\Models\Shop\Client|null $existing */
        $existing = \App\Models\Shop\Client::whereIn('phone', $candidates)->first();

        if ($existing) {
            // если уже верифицирован — это полноценный дубль
            if ($existing->phone_verified_at) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Цей номер вже зареєстрований. Увійдіть у кабінет.',
                    'errors' => ['phone' => ['phone_already_taken']],
                ], 422);
            }

            // не верифицирован: разрешаем отправку коду як завершення реєстрації
            // можно подставить name/email/password из запроса в payload,
            // но НЕ создавать новую запись сейчас.
        }

        // 4) кладём payload у cache на TTL і надсилаємо SMS
        $ttl         = (int) env('ESPUTNIK_SMS_TTL', 180);
        $resendAfter = (int) env('ESPUTNIK_SMS_RESEND_AFTER', 60);
        $key         = 'reg:'.$digits;

        if (Cache::has($key.':resend_lock')) {
            throw ValidationException::withMessages(['phone' => 'Повторна відправка поки недоступна.']);
        }

        $code = random_int(1000,9999);

        Cache::put($key, [
            'code'    => (string)$code,
            'payload' => [
                'name'     => $data['name'],
                'phone'    => $digits,                 // нормализованный!
                'email'    => $data['email'] ?? null,
                'password' => bcrypt($r->input('password')),
            ],
            'attempts' => 0,
        ], $ttl);

        Cache::put($key.':resend_lock', 1, $resendAfter);

        $resp = $sms->sendCode($digits, (string)$code, null, [
            'message_type' => 'register',
            'client_id' => $existing?->id,
            'raw_phone' => (string) $r->input('phone'),
        ]);
        if (($resp['status'] ?? 500) >= 300) {
            Cache::forget($key);
            Cache::forget($key.':resend_lock');
            return response()->json([
                'ok' => false,
                'message' => 'Не вдалося відправити SMS. Перевірте відправника та баланс.',
            ], 422);
        }

        return response()->json(['ok'=>true, 'ttl'=>$ttl, 'resend_in'=>$resendAfter]);
    }


    public function sendSms(Request $r)
    {
        $r->validate(['phone'=>'required']);
        session([
            'client_verify_code'  => '1234',
            'client_verify_phone' => $r->string('phone'),
        ]);
        return response()->json(['ok'=>true]);
    }

    public function verifySms(Request $r)
    {
        $digits = $this->normalizePhone((string)$r->input('phone'));
        $r->merge(['phone' => $digits]);

        $data = $r->validate([
            'phone' => ['required','regex:/^380\d{9}$/'],
            'code'  => ['required','digits:4'],
        ]);

        $key   = 'reg:'.$data['phone'];
        $state = Cache::get($key);

        if (!$state) {
            return response()->json([
                'message' => 'Код прострочений. Запросіть новий.',
                'errors'  => ['code' => ['expired']],
            ], 422);
        }

        if ((string)$state['code'] !== (string)$data['code']) {
            return response()->json([
                'message' => st('auth.code_invalid', 'Невірний код. Перевірте цифри та спробуйте ще раз.'),
                'errors'  => ['code' => ['invalid']],
            ], 422);
        }

        $p = $state['payload'];


        $client = Client::firstOrCreate(
            ['phone' => $data['phone']],                        // нормализованный
            [
                'name'     => $p['name'] ?? 'Клієнт',
                'email'    => $p['email'] ?? null,
                'password' => $p['password'],                   // уже bcrypt
            ]
        );

        if (is_null($client->phone_verified_at)) {
            $client->phone_verified_at = now();
            $client->save();
        }

        $this->guard()->login($client, true);

        Cache::forget($key);
        Cache::forget($key.':resend_lock');

        return response()->json([
            'ok' => true,
            'redirect' => $this->getRedirectUrl($r),
        ]);
    }


    private function normalizePhone(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw);   // тільки цифри
        if (Str::startsWith($d, '0'))  $d = '38'.$d; // 0XXXXXXXXX -> 380XXXXXXXXX
        if (Str::startsWith($d, '3800')) $d = '380' . substr($d, 4);
        if (strlen($d) === 9)          $d = '380'.$d;
        if (Str::startsWith($d, '380') === false && strlen($d) >= 10) {
            // залишаємо останні 9 цифр + 380 (на випадок вставок з +38)
            $d = '380'.substr($d, -9);
        }
        return $d;
    }

    public function login(Request $r)
    {
        // 1) Нормализация ввода -> 380XXXXXXXXX
        $raw    = (string) $r->input('phone');
        $digits = preg_replace('/\D+/', '', $raw);     // "+38 0XX ..." -> "380XXXXXXXXX"
        if (str_starts_with($digits, '0'))  $digits = '38' . $digits; // 0XXXXXXXXX -> 380XXXXXXXXX
        if (strlen($digits) === 9)          $digits = '380' . $digits;

        // для validate() подставляем уже нормализованное
        $r->merge(['phone' => $digits]);

        // 2) Валидация с понятными сообщениями
        $r->validate(
            [
                'phone'    => ['required', 'regex:/^380\d{9}$/'],
                'password' => ['required', 'string'],
            ],
            [
                'phone.required'   => 'Вкажіть номер телефону',
                'phone.regex'      => 'Невірний формат номера телефону. Приклад: +38 0XX XXX XX XX',
                'password.required'=> 'Вкажіть пароль',
            ]
        );

        // 3) Поиск пользователя по всем возможным форматам в БД
        //    приоритет: точное совпадение 380XXXXXXXXX, затем 0XXXXXXXXX, затем XXXXXXXXX
        $candidates = array_unique([
            $digits,                                // 380XXXXXXXXX
            '0' . substr($digits, 3),               // 0XXXXXXXXX
            substr($digits, 3),                     // XXXXXXXXX
        ]);

        $user = \App\Models\Shop\Client::query()
            ->where(function ($q) use ($candidates) {
                foreach ($candidates as $v) {
                    $q->orWhere('phone', $v);
                }
            })
            // упорядочим так, чтобы сначала шел правильный формат
            ->when(function () { return true; }, function ($q) use ($candidates) {
                $placeholders = implode(',', array_fill(0, count($candidates), '?'));
                $q->orderByRaw("FIELD(phone, $placeholders)", $candidates);
            })
            ->first();

        // 4) Проверка пароля и единое сообщение об ошибке
        if (!$user || !\Hash::check($r->input('password'), $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => 'Невірний логін або пароль',
            ]);
        }

        // 5) Мягкая миграция: если в БД был старый формат — приводим к 380XXXXXXXXX
        if ($user->phone !== $digits) {
            try {
                $user->phone = $digits;
                $user->save();
            } catch (\Throwable $e) {
                // если внезапно конфликт уникальности — просто пропустим миграцию
            }
        }

        // 6) Логин
        $this->guard()->login($user, true);
        $r->session()->regenerate();

        return response()->json([
            'ok' => true,
            'redirect' => $this->getRedirectUrl($r),
        ]);
    }



    // === Авторизация только по телефону + SMS (без пароля) ===
    public function loginPhoneSms(Request $r, \App\Services\Sms\EsputnikSms $sms)
    {
        // 1) Нормализация ввода -> 380XXXXXXXXX
        $raw    = (string) $r->input('phone');
        $digits = $this->normalizePhone($raw);
        $r->merge(['phone' => $digits]);

        // 2) Валидация
        $r->validate(
            [
                'phone' => ['required', 'regex:/^380\d{9}$/'],
            ],
            [
                'phone.required' => 'Вкажіть номер телефону',
                'phone.regex'    => 'Невірний формат номера телефону. Приклад: +38 0XX XXX XX XX',
            ]
        );

        // 3) Проверяем, существует ли пользователь
        $candidates = array_unique([
            $digits,
            '0' . substr($digits, 3),
            substr($digits, 3),
        ]);

        $client = Client::query()
            ->where(function ($q) use ($candidates) {
                foreach ($candidates as $v) {
                    $q->orWhere('phone', $v);
                }
            })
            ->first();

        // Если пользователя нет, создаем его автоматически (без имени, только номер телефона)
        if (!$client) {
            $client = new Client();
            $client->phone = $digits;
            // Имя оставляем null - пользователь сможет заполнить его в профиле позже
            $client->password = Hash::make(Str::random(24)); // Генерируем случайный пароль
            $client->save();
        }

        // 4) Отправка SMS кода
        $ttl         = (int) env('ESPUTNIK_SMS_TTL', 180);
        $resendAfter = (int) env('ESPUTNIK_SMS_RESEND_AFTER', 60);
        $key         = 'login_sms:' . $digits;

        if (Cache::has($key . ':resend_lock')) {
            return response()->json([
                'ok'      => false,
                'message' => 'Повторна відправка поки недоступна. Спробуйте через ' . $resendAfter . ' секунд.',
            ], 422);
        }

        // Генерируем одноразовый код и сохраняем состояние
        $code = (string) random_int(1000, 9999);

        Cache::put($key, [
            'code'      => $code,
            'client_id' => $client->id,
            'attempts'  => 0,
        ], $ttl);

        Cache::put($key . ':resend_lock', 1, $resendAfter);

        // Реальная отправка SMS через API
        $resp = $sms->sendCode($digits, $code, null, [
            'message_type' => 'login',
            'client_id' => $client->id,
            'raw_phone' => (string) $r->input('phone'),
        ]);
        if (($resp['status'] ?? 500) >= 300) {
            Log::error('SMS отправка не удалась', [
                'sms_log_id' => $resp['log_id'] ?? null,
                'phone' => $digits,
                'status' => $resp['status'] ?? 'unknown',
            ]);
            
            Cache::forget($key);
            Cache::forget($key . ':resend_lock');
            return response()->json([
                'ok'      => false,
                'message' => 'Не вдалося відправити SMS. Перевірте відправника та баланс.',
            ], 422);
        }
        
        return response()->json([
            'ok'        => true,
            'ttl'       => $ttl,
            'resend_in' => $resendAfter,
        ]);
    }

    public function verifyPhoneSms(Request $r)
    {
        $digits = $this->normalizePhone((string) $r->input('phone'));
        $r->merge(['phone' => $digits]);

        $data = $r->validate([
            'phone' => ['required', 'regex:/^380\d{9}$/'],
            'code'  => ['required', 'digits:4'],
        ], [
            'phone.required' => 'Вкажіть номер телефону',
            'phone.regex'    => 'Невірний формат номера телефону',
            'code.required'  => 'Введіть 4 цифри',
            'code.digits'    => 'Введіть 4 цифри',
        ]);

        $key   = 'login_sms:' . $data['phone'];
        $state = Cache::get($key);

        if (!$state) {
            return response()->json([
                'ok'      => false,
                'message' => 'Код прострочений. Запросіть новий.',
                'errors'  => ['code' => ['expired']],
            ], 422);
        }

        $attempts = (int) ($state['attempts'] ?? 0);
        if ($attempts >= 5) {
            Cache::forget($key);
            return response()->json([
                'ok'      => false,
                'message' => 'Забагато спроб. Спробуйте пізніше або надішліть код ще раз.',
                'errors'  => ['code' => ['too_many_attempts']],
            ], 422);
        }

        // Проверяем код из кэша
        if ((string) $state['code'] !== (string) $data['code']) {
            $state['attempts'] = $attempts + 1;
            Cache::put($key, $state, now()->addMinutes(3));
            return response()->json([
                'ok'      => false,
                'message' => st('auth.code_invalid', 'Невірний код. Перевірте цифри та спробуйте ще раз.'),
                'errors'  => ['code' => ['invalid']],
            ], 422);
        }

        // Находим клиента
        $client = Client::find($state['client_id']);
        if (!$client) {
            Cache::forget($key);
            return response()->json([
                'ok'      => false,
                'message' => 'Клієнт не знайдений.',
            ], 422);
        }

        // Обновляем телефон до нормализованного формата, если нужно
        if ($client->phone !== $digits) {
            try {
                $client->phone = $digits;
                $client->save();
            } catch (\Throwable $e) {
                // Игнорируем ошибку, если номер уже используется
            }
        }

        // Проверяем верификацию телефона
        if (is_null($client->phone_verified_at)) {
            $client->phone_verified_at = now();
            $client->save();
        }

        // Логин с remember на 30 дней
        $this->guard()->login($client, true);
        $r->session()->regenerate();

        Cache::forget($key);
        Cache::forget($key . ':resend_lock');

        return response()->json([
            'ok'       => true,
            'redirect' => $this->getRedirectUrl($r),
        ]);
    }

    /**
     * Сохранить URL checkout для редиректа после авторизации
     */
    public function saveCheckoutUrl(Request $request)
    {
        $url = $request->input('url');
        
        // Логируем для отладки
        \Log::info('saveCheckoutUrl called', [
            'url' => $url,
            'session_id' => $request->session()->getId(),
        ]);
        
        if ($url && str_contains($url, '/checkout')) {
            $request->session()->put('auth.redirect_to_checkout', $url);
            
            // Логируем после сохранения
            \Log::info('saveCheckoutUrl saved', [
                'saved_url' => $request->session()->get('auth.redirect_to_checkout'),
                'session_id' => $request->session()->getId(),
            ]);
        }
        return response()->json(['ok' => true]);
    }

    public function logout(Request $r)
    {
        $this->guard()->logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return back();
    }
}
