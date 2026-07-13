<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Pages;
use App\Services\SiteTemplates\SiteTemplateRenderer;

class PageController extends Controller
{
    /**
     * Показ страницы, когда модель уже найдена в роуте
     * (как в твоём коде: app(PageController::class)->show($page))
     */
    public function show(Pages $page)
    {
        // Пытаемся отрендерить спец-шаблон по слагу, если он есть
        $view = front_view('pages.' . $page->slug);   // например resources/views/front/{theme}/pages/delivery.blade.php

        if (view()->exists($view)) {
            return app(SiteTemplateRenderer::class)->render($view, $view, compact('page'));
        }

        // Фолбэк – общий шаблон для всех статических страниц
        $fallbackView = front_view('pages.show');

        return app(SiteTemplateRenderer::class)->render($fallbackView, $fallbackView, compact('page')); // resources/views/front/{theme}/pages/show.blade.php
    }

    /**
     * Альтернативный вариант, если захочешь вызывать по слагу прямо из маршрута:
     *
     * Route::get('/{slug}', [PageController::class, 'showBySlug']);
     */
    public function showBySlug(string $slug)
    {
        $page = Pages::query()->where('slug', $slug)->firstOrFail();

        return $this->show($page);
    }
}
