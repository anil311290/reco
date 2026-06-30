<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        Theme::firstOrCreate(
            ['name' => 'Default Light', 'is_default' => true],
            [
                'primary_color' => '#6366f1',
                'secondary_color' => '#8b5cf6',
                'accent_color' => '#06b6d4',
                'sidebar_color' => '#1e1b4b',
                'header_color' => '#ffffff',
                'text_color' => '#1f2937',
                'bg_color' => '#f9fafb',
                'font_family' => 'Inter',
                'dark_mode' => false,
                'is_active' => true,
            ]
        );

        Theme::firstOrCreate(
            ['name' => 'Default Dark'],
            [
                'primary_color' => '#818cf8',
                'secondary_color' => '#a78bfa',
                'accent_color' => '#22d3ee',
                'sidebar_color' => '#0f0d2e',
                'header_color' => '#111827',
                'text_color' => '#f9fafb',
                'bg_color' => '#111827',
                'font_family' => 'Inter',
                'dark_mode' => true,
                'is_active' => true,
                'is_default' => false,
            ]
        );
    }
}
