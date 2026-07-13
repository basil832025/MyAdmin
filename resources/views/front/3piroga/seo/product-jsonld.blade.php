@php
    /**
     * Schema.org JSON-LD (Product page) in @graph format.
     * Expects: $product (array), optional $category, $stats, $reviews.
     */

    $locale = app()->getLocale();
    $baseUrl = rtrim((string) request()->getSchemeAndHttpHost(), '/');
    $homeUrl = $baseUrl . '/';

    $orgId = $homeUrl . '#organization';
    $restaurantId = $homeUrl . '#restaurant';

    $host = (string) parse_url($homeUrl, PHP_URL_HOST);
    $orgName = $host !== '' ? $host : trim((string) config('app.name'));

    $restaurantName = st('all.try-pyroha', 'Три пироги');

    $logoUrl = asset('images/logo-footer.png');

    $sameAs = array_values(array_filter([
        'https://www.facebook.com/3piroga.ua',
        'https://www.instagram.com/3piroga_ua',
        'https://t.me/OsetianBakery',
        'https://www.youtube.com/channel/UC37VV_ZFmkTacWeHKFsYWLQ',
    ]));

    $phones = collect($headerPhones ?? []);
    $primaryPhone = $headerPhonePrimary['tel'] ?? $phones->first()['tel'] ?? '';
    $primaryPhone = trim((string) $primaryPhone);

    $emails = (array) data_get($headerLocation ?? null, 'emails', []);
    $email = (string) (($emails[0]['email'] ?? '') ?: ($emails[0] ?? ''));
    $email = trim($email);
    if ($email === '') {
        $email = (string) (config('notifications.order_notification_email.0') ?? 'info@3piroga.ua');
    }

    $addressStreet = '';
    $addressCity = '';
    if (!empty($headerLocation)) {
        if (method_exists($headerLocation, 'getTranslation')) {
            $addressStreet = (string) ($headerLocation->getTranslation('address', $locale) ?: $headerLocation->getTranslation('address', 'uk') ?: '');
            $addressCity = (string) ($headerLocation->getTranslation('city', $locale) ?: $headerLocation->getTranslation('city', 'uk') ?: '');
        } else {
            $addressStreet = (string) data_get($headerLocation, "address.$locale", data_get($headerLocation, 'address.uk', ''));
            $addressCity = (string) data_get($headerLocation, "city.$locale", data_get($headerLocation, 'city.uk', ''));
        }
    }
    $addressStreet = trim(preg_replace('/\s+/u', ' ', strip_tags($addressStreet)));
    $addressCity = trim(preg_replace('/\s+/u', ' ', strip_tags($addressCity)));

    $productUrl = url()->current();
    $productId = $productUrl . '#product';
    $breadcrumbId = $productUrl . '#breadcrumb';

    $name = trim((string) ($product['title'] ?? ''));
    $slug = trim((string) ($product['slug'] ?? ''));
    $sku = $slug !== '' ? $slug : trim((string) ($product['article'] ?? ''));

    $img = trim((string) ($product['main_image'] ?? ''));
    if ($img !== '' && !\Illuminate\Support\Str::startsWith($img, ['http://', 'https://'])) {
        $img = url($img);
    }

    $descSource = trim((string) ($product['seo_description'] ?? ''));
    if ($descSource === '') $descSource = trim((string) ($product['ingredients_text'] ?? ''));
    if ($descSource === '') $descSource = trim((string) ($product['description'] ?? ''));
    $description = trim(preg_replace('/\s+/u', ' ', strip_tags($descSource)));

    $categoryName = '';
    $categorySlug = '';
    if (!empty($category)) {
        $categorySlug = trim((string) ($category->slug ?? ''));
        if (method_exists($category, 'getTranslation')) {
            $categoryName = (string) ($category->getTranslation('title', $locale) ?: $category->getTranslation('title', 'uk') ?: '');
        }
        if ($categoryName === '') {
            $categoryName = (string) ($category->name ?? $category->title ?? '');
        }
    }
    $categoryName = trim(preg_replace('/\s+/u', ' ', strip_tags($categoryName)));

    $rows = $product['variant_rows'] ?? [];
    $prices = [];
    foreach ($rows as $row) {
        $p = (float) ($row['price'] ?? 0);
        if ($p > 0) $prices[] = $p;
    }
    $priceValue = !empty($prices) ? min($prices) : (float) ($product['price'] ?? 0);
    $priceText = $priceValue > 0 ? (string) ((int) $priceValue == $priceValue ? (int) $priceValue : $priceValue) : '';

    $graph = [];

    // Organization
    $graph[] = array_filter([
        '@type' => 'Organization',
        '@id' => $orgId,
        'name' => $orgName,
        'url' => $homeUrl,
        'logo' => $logoUrl,
        'sameAs' => !empty($sameAs) ? $sameAs : null,
    ], fn ($v) => $v !== null && $v !== '');

    // Restaurant
    $restaurant = [
        '@type' => 'Restaurant',
        '@id' => $restaurantId,
        'name' => $restaurantName,
        'url' => $homeUrl,
        'image' => $img !== '' ? $img : null,
        'telephone' => $primaryPhone !== '' ? $primaryPhone : null,
        'email' => $email !== '' ? $email : null,
        'address' => ($addressStreet !== '' || $addressCity !== '') ? array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $addressStreet !== '' ? $addressStreet : null,
            'addressLocality' => $addressCity !== '' ? $addressCity : null,
            'addressCountry' => 'UA',
        ], fn ($v) => $v !== null && $v !== '') : null,
        'servesCuisine' => 'Осетинські пироги',
        'priceRange' => '$$',
        'areaServed' => $addressCity !== '' ? ['@type' => 'City', 'name' => $addressCity] : null,
    ];
    $graph[] = array_filter($restaurant, fn ($v) => $v !== null && $v !== '');

    // Product
    $offer = array_filter([
        '@type' => 'Offer',
        'url' => $productUrl,
        'priceCurrency' => 'UAH',
        'price' => $priceText !== '' ? $priceText : null,
        'availability' => 'https://schema.org/InStock',
        'itemCondition' => 'https://schema.org/NewCondition',
        'seller' => ['@id' => $orgId],
    ], fn ($v) => $v !== null && $v !== '');

    $productNode = [
        '@type' => 'Product',
        '@id' => $productId,
        'name' => $name,
        'image' => $img !== '' ? [$img] : null,
        'description' => $description !== '' ? $description : null,
        'brand' => ['@type' => 'Brand', 'name' => $orgName],
        'sku' => $sku !== '' ? $sku : null,
        'category' => $categoryName !== '' ? $categoryName : null,
        'offers' => $offer,
    ];

    $totalReviews = (int) data_get($stats ?? null, 'total', 0);
    $avgRating = (float) data_get($stats ?? null, 'avg_rating', 0);
    if ($totalReviews > 0 && $avgRating > 0) {
        $productNode['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) round($avgRating, 2),
            'reviewCount' => (string) $totalReviews,
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }

    $reviewItems = [];
    if (!empty($reviews) && method_exists($reviews, 'items')) {
        foreach (array_slice($reviews->items(), 0, 5) as $r) {
            $authorName = trim((string) ($r->name ?? ''));
            $body = trim((string) ($r->content ?? ''));
            $rating = (int) ($r->rating ?? 0);
            $date = '';
            if (!empty($r->created_at) && method_exists($r->created_at, 'format')) {
                $date = $r->created_at->format('Y-m-d');
            }

            if ($authorName === '' && $body === '' && $rating <= 0) continue;

            $reviewItems[] = array_filter([
                '@type' => 'Review',
                'author' => $authorName !== '' ? ['@type' => 'Person', 'name' => $authorName] : null,
                'datePublished' => $date !== '' ? $date : null,
                'reviewBody' => $body !== '' ? $body : null,
                'reviewRating' => ($rating > 0) ? [
                    '@type' => 'Rating',
                    'ratingValue' => (string) $rating,
                    'bestRating' => '5',
                    'worstRating' => '1',
                ] : null,
            ], fn ($v) => $v !== null && $v !== '');
        }
    }
    if (!empty($reviewItems)) {
        $productNode['review'] = $reviewItems;
    }

    $graph[] = array_filter($productNode, fn ($v) => $v !== null && $v !== '');

    // BreadcrumbList
    $localePrefix = in_array($locale, ['ru', 'en'], true) ? ('/' . $locale) : '';
    $allMenuUrl = in_array($locale, ['ru', 'en'], true)
        ? route('localized.catalog.index', ['locale' => $locale])
        : route('catalog.index');

    $categoryUrl = null;
    if ($categorySlug !== '') {
        $categoryUrl = $baseUrl . $localePrefix . '/' . ltrim($categorySlug, '/');
    }

    $bread = [];
    $bread[] = [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => st('menu.home', 'Головна'),
        'item' => $homeUrl,
    ];
    $bread[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => st('menu.all_menu', 'Все меню'),
        'item' => $allMenuUrl,
    ];
    if ($categoryName !== '' && $categoryUrl) {
        $bread[] = [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $categoryName,
            'item' => $categoryUrl,
        ];
        $bread[] = [
            '@type' => 'ListItem',
            'position' => 4,
            'name' => $name,
            'item' => $productUrl,
        ];
    } else {
        $bread[] = [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $name,
            'item' => $productUrl,
        ];
    }

    $graph[] = [
        '@type' => 'BreadcrumbList',
        '@id' => $breadcrumbId,
        'itemListElement' => $bread,
    ];

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];

    $jsonLdText = json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $jsonLdText = is_string($jsonLdText) ? str_replace('</', '<\/', $jsonLdText) : '';
@endphp

<script type="application/ld+json">{!! $jsonLdText !!}</script>
