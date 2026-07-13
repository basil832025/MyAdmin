<?php

if (! function_exists('front_view')) {
    function front_view(string $view, ?string $theme = null): string
    {
        $theme ??= (string) config('project.theme', '3piroga');
        $theme = trim($theme);

        if ($theme === '') {
            return $view;
        }

        $themedView = 'front.' . $theme . '.' . ltrim($view, '.');

        return view()->exists($themedView) ? $themedView : $view;
    }
}