<?php

declare(strict_types=1);

namespace App\Pixury\Templates;

use Illuminate\Support\Facades\Cache;

class TemplateRepository
{
    /**
     * Canonical aspect-ratio presets supported by Pixury.
     * Single source of truth: templates store these, the job converts them once
     * into the format consumed by FalAIService::generate().
     */
    public const ASPECT_RATIOS = ['1:1', '4:5', '16:9', '9:16', '3:2', '4:3'];

    public function getAll(): array
    {
        return Cache::remember('pixury.templates.all', 3600, function () {
            return [
                [
                    'id'          => 'product-showcase',
                    'name'        => 'Product Showcase',
                    'description' => 'Clean product photography with studio lighting',
                    'category'    => 'E-commerce',
                    'thumbnail'   => asset('images/pixury/templates/product-showcase.svg'),
                    'tags'        => ['product', 'ecommerce', 'clean'],
                    'aspect_ratio' => '1:1',
                    'resolution'  => '1024x1024',
                ],
                [
                    'id'          => 'lifestyle-shot',
                    'name'        => 'Lifestyle Shot',
                    'description' => 'Product in real-world context',
                    'category'    => 'E-commerce',
                    'thumbnail'   => asset('images/pixury/templates/lifestyle-shot.svg'),
                    'tags'        => ['lifestyle', 'context', 'natural'],
                    'aspect_ratio' => '4:5',
                    'resolution'  => '1024x1280',
                ],
                [
                    'id'          => 'hero-banner',
                    'name'        => 'Hero Banner',
                    'description' => 'Wide banner for website headers',
                    'category'    => 'Marketing',
                    'thumbnail'   => asset('images/pixury/templates/hero-banner.svg'),
                    'tags'        => ['banner', 'marketing', 'wide'],
                    'aspect_ratio' => '16:9',
                    'resolution'  => '1920x1080',
                ],
                [
                    'id'          => 'social-post',
                    'name'        => 'Social Media Post',
                    'description' => 'Square format for Instagram/Facebook',
                    'category'    => 'Social Media',
                    'thumbnail'   => asset('images/pixury/templates/social-post.svg'),
                    'tags'        => ['social', 'instagram', 'square'],
                    'aspect_ratio' => '1:1',
                    'resolution'  => '1080x1080',
                ],
                [
                    'id'          => 'story-format',
                    'name'        => 'Story Format',
                    'description' => 'Vertical format for Stories/Reels/TikTok',
                    'category'    => 'Social Media',
                    'thumbnail'   => asset('images/pixury/templates/story-format.svg'),
                    'tags'        => ['story', 'reels', 'tiktok', 'vertical'],
                    'aspect_ratio' => '9:16',
                    'resolution'  => '1080x1920',
                ],
                [
                    'id'          => 'product-comparison',
                    'name'        => 'Product Comparison',
                    'description' => 'Side-by-side product comparison',
                    'category'    => 'E-commerce',
                    'thumbnail'   => asset('images/pixury/templates/product-comparison.svg'),
                    'tags'        => ['comparison', 'vs', 'decision'],
                    'aspect_ratio' => '3:2',
                    'resolution'  => '1536x1024',
                ],
                [
                    'id'          => 'flat-lay',
                    'name'        => 'Flat Lay',
                    'description' => 'Top-down styled product photography',
                    'category'    => 'E-commerce',
                    'thumbnail'   => asset('images/pixury/templates/flat-lay.svg'),
                    'tags'        => ['flatlay', 'styled', 'top-down'],
                    'aspect_ratio' => '1:1',
                    'resolution'  => '1024x1024',
                ],
                [
                    'id'          => 'minimal-product',
                    'name'        => 'Minimal Product',
                    'description' => 'Clean white background product shot',
                    'category'    => 'E-commerce',
                    'thumbnail'   => asset('images/pixury/templates/minimal-product.svg'),
                    'tags'        => ['minimal', 'white-bg', 'catalog'],
                    'aspect_ratio' => '1:1',
                    'resolution'  => '1024x1024',
                ],
                [
                    'id'          => 'bundle-pack',
                    'name'        => 'Bundle Pack',
                    'description' => 'Multiple products in one composition',
                    'category'    => 'E-commerce',
                    'thumbnail'   => asset('images/pixury/templates/bundle-pack.svg'),
                    'tags'        => ['bundle', 'pack', 'multiple'],
                    'aspect_ratio' => '4:3',
                    'resolution'  => '1365x1024',
                ],
                [
                    'id'          => 'seasonal-campaign',
                    'name'        => 'Seasonal Campaign',
                    'description' => 'Holiday/seasonal themed product imagery',
                    'category'    => 'Marketing',
                    'thumbnail'   => asset('images/pixury/templates/seasonal-campaign.svg'),
                    'tags'        => ['seasonal', 'holiday', 'campaign'],
                    'aspect_ratio' => '16:9',
                    'resolution'  => '1920x1080',
                ],
            ];
        });
    }

    public function getByCategory(string $category): array
    {
        return array_filter($this->getAll(), fn ($t) => $t['category'] === $category);
    }

    public function getCategories(): array
    {
        return array_unique(array_column($this->getAll(), 'category'));
    }

    public function findById(string $id): ?array
    {
        return collect($this->getAll())->firstWhere('id', $id) ?? null;
    }
}
