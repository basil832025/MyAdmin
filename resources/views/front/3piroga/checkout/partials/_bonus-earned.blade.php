<div class="mt-2 text-[13px] leading-[18px] text-[#6B7280] flex items-center gap-1">
    <span class="text-[18px]" aria-hidden="true">
        {{-- иконка бонусов --}}
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
             xmlns="http://www.w3.org/2000/svg">
            <path d="M4 11V19C4 19.5304 4.21071 20.0391 4.58579 20.4142C4.96086 20.7893 5.46957 21 6 21H18C18.5304 21 19.0391 20.7893 19.4142 20.4142C19.7893 20.0391 20 19.5304 20 19V11" stroke="#CC5502" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 5.5C12 4.57174 11.6313 3.6815 10.9749 3.02513C10.3185 2.36875 9.42826 2 8.5 2C7.83696 2 7.20107 2.26339 6.73223 2.73223C6.26339 3.20107 6 3.83696 6 4.5C6 5.16304 6.26339 5.79893 6.73223 6.26777C7.20107 6.73661 7.83696 7 8.5 7H12M12 5.5V7M12 5.5C12 4.57174 12.3687 3.6815 13.0251 3.02513C13.6815 2.36875 14.5717 2 15.5 2C16.163 2 16.7989 2.26339 17.2678 2.73223C17.7366 3.20107 18 3.83696 18 4.5C18 4.8283 17.9353 5.15339 17.8097 5.45671C17.6841 5.76002 17.4999 6.03562 17.2678 6.26777C17.0356 6.49991 16.76 6.68406 16.4567 6.8097C16.1534 6.93534 15.8283 7 15.5 7H12" stroke="#CC5502" stroke-width="1.5" stroke-linejoin="round"/>
            <path d="M12 11V21M3 7H21V11H3V7Z" stroke="#CC5502" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
    <span>
      {{ st('cart.za-pokupku-narahovano', 'За покупку вам буде нараховано') }}
        <span class="text-[#FF7500] font-semibold" data-checkout-bonus-earn>
            {{ $totals['bonus_earn'] ?? 0 }}
        </span> {{ st('cart.balliv', 'балів') }}
    </span>
</div>
