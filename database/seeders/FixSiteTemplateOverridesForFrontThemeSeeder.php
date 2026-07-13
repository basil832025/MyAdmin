<?php

namespace Database\Seeders;

use App\Models\SiteTemplateOverride;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FixSiteTemplateOverridesForFrontThemeSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private array $paths = [
        'home' => 'resources/views/front/3piroga/home.blade.php',
        'pages.about' => 'resources/views/front/3piroga/pages/about.blade.php',
        'pages.blog.index' => 'resources/views/front/3piroga/pages/blog/index.blade.php',
        'pages.blog.show' => 'resources/views/front/3piroga/pages/blog/show.blade.php',
        'pages.delivery' => 'resources/views/front/3piroga/pages/delivery.blade.php',
        'pages.nas-blagodaryat' => 'resources/views/front/3piroga/pages/nas-blagodaryat.blade.php',
        'pages.nashi-restorany' => 'resources/views/front/3piroga/pages/nashi-restorany.blade.php',
        'pages.reviews' => 'resources/views/front/3piroga/pages/reviews.blade.php',
        'pages.show' => 'resources/views/front/3piroga/pages/show.blade.php',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->paths as $key => $sourcePath) {
                $template = SiteTemplateOverride::query()->where('key', $key)->first();

                if (! $template) {
                    $this->command?->warn("Site template override not found: {$key}");
                    continue;
                }

                $absolutePath = base_path($sourcePath);
                if (! File::exists($absolutePath)) {
                    $this->command?->warn("Source view not found for {$key}: {$sourcePath}");
                    continue;
                }

                $originalSnapshot = File::get($absolutePath);
                $overrideBody = (string) ($template->override_body ?? '');

                $template->forceFill([
                    'source_path' => $sourcePath,
                    'original_snapshot' => $originalSnapshot,
                    'original_hash' => sha1($originalSnapshot),
                    'last_synced_at' => now(),
                    'override_body' => $overrideBody !== ''
                        ? $this->rewriteLegacyViewReferences($overrideBody)
                        : $template->override_body,
                ])->saveQuietly();

                $this->command?->info("Updated site template override: {$key}");
            }
        });
    }

    private function rewriteLegacyViewReferences(string $body): string
    {
        $body = str_replace(
            ["@extends('layouts.app')", '@extends("layouts.app")'],
            "@extends(front_view('layouts.app'))",
            $body
        );

        foreach (['partials.', 'pages.', 'product.', 'checkout.', 'cart.', 'components.', 'seo.'] as $prefix) {
            $quotedPrefix = preg_quote($prefix, '/');

            $body = preg_replace(
                "/@include\(\s*'({$quotedPrefix}[^']*)'\s*,/",
                "@include(front_view('$1'),",
                $body
            ) ?? $body;

            $body = preg_replace(
                "/@include\(\s*'({$quotedPrefix}[^']*)'\s*\)/",
                "@include(front_view('$1'))",
                $body
            ) ?? $body;

            $body = preg_replace(
                "/@include\(\s*\"({$quotedPrefix}[^\"]*)\"\s*,/",
                "@include(front_view('$1'),",
                $body
            ) ?? $body;

            $body = preg_replace(
                "/@include\(\s*\"({$quotedPrefix}[^\"]*)\"\s*\)/",
                "@include(front_view('$1'))",
                $body
            ) ?? $body;
        }

        return $body;
    }
}