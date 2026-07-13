@php
    use App\Models\Banner;
    use App\Models\Setting;

    $locale = app()->getLocale();
    $now = now();
    $delaySeconds = (int) Setting::admin('site.banner_rotation_delay_seconds', 10);
    if ($delaySeconds < 1) $delaySeconds = 10;
    if ($delaySeconds > 120) $delaySeconds = 120;
    $delayMs = $delaySeconds * 1000;

    // Загружаем активные баннеры
    $banners = Banner::query()
        ->where('is_active', true)
        ->where(function ($q) use ($now) {
            $q->whereNull('starts_at')
              ->orWhere('starts_at', '<=', $now);
        })
        ->where(function ($q) use ($now) {
            $q->whereNull('ends_at')
              ->orWhere('ends_at', '>=', $now);
        })
        ->orderBy('sort')
        ->get();

    // Dynamic priority by weekday/time schedule
    $banners = $banners
        ->map(function (Banner $b) use ($now) {
            $b->__activeSchedulePriority = $b->schedulePriorityAt($now);
            return $b;
        })
        ->sort(function (Banner $a, Banner $b) {
            $pa = $a->__activeSchedulePriority;
            $pb = $b->__activeSchedulePriority;

            $aActive = $pa !== null;
            $bActive = $pb !== null;
            if ($aActive !== $bActive) return $aActive ? -1 : 1;

            if ($aActive && $bActive && $pa !== $pb) return ($pa > $pb) ? -1 : 1;

            // fallback to existing sort, then id
            if ((int)$a->sort !== (int)$b->sort) return ((int)$a->sort < (int)$b->sort) ? -1 : 1;
            return ((int)$a->id < (int)$b->id) ? -1 : 1;
        })
        ->values();

    $slides = $banners
        ->map(function (Banner $banner) use ($locale) {
            $imagePath = $banner->getImageForLocale($locale);
            if (! $imagePath) {
                return null;
            }

            $mobilePath = $banner->getMobileImageForLocale($locale) ?: $imagePath;
            $title = $banner->getTranslation('title', $locale)
                ?? $banner->title
                ?? 'Банер';

            return [
                'image' => $imagePath,
                'mobile' => $mobilePath,
                'title' => $title,
                'url' => $banner->getLocalizedUrl($locale),
                'target' => $banner->target,
            ];
        })
        ->filter()
        ->values();
@endphp

@if($slides->isNotEmpty())
    <div class="swiper banner-swiper relative rounded-2xl" data-autoplay-delay-ms="{{ $delayMs }}">
        <div class="swiper-wrapper">
            @foreach($slides as $slide)
                <div class="swiper-slide">
                    @if(!empty($slide['url']))
                        <a href="{{ $slide['url'] }}" @if(($slide['target'] ?? null) === '_blank') target="_blank" rel="noopener" @endif>
                    @endif

                            <div class="banner-slide-inner">
                                <picture>
                                    @if(!empty($slide['mobile']) && $slide['mobile'] !== $slide['image'])
                                        <source srcset="{{ asset('storage/' . $slide['mobile']) }}" media="(max-width: 768px)">
                                    @endif
                                    <img src="{{ asset('storage/' . $slide['image']) }}"
                                         alt="{{ $slide['title'] }}"
                                         class="banner-img w-full h-full object-contain">
                                </picture>
                            </div>

                    @if(!empty($slide['url']))
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="swiper-button-prev banner-arrow">
            <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15.0901 4.61184L8.57009 11.1318C7.80009 11.9018 7.80009 13.1618 8.57009 13.9318L15.0901 20.4518" fill="#272828"/>
            </svg>
        </div>

        <div class="swiper-button-next banner-arrow">
            <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8.90991 20.4519L15.4299 13.9319C16.1999 13.1619 16.1999 11.9019 15.4299 11.1319L8.90991 4.61188" fill="#272828"/>
            </svg>
        </div>
    </div>

    <div id="banner-pagination" class="mt-3 flex justify-center"></div>
@endif
