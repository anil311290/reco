<?php

namespace Database\Seeders;

use App\Models\WebsitePage;
use Illuminate\Database\Seeder;

class WebsitePageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'home',
                'title' => 'Home',
                'meta_title' => 'Reco - Offline Accounting SaaS',
                'meta_description' => 'Modern offline-first accounting software for businesses of all sizes.',
                'template' => 'landing',
                'status' => 'published',
                'show_in_nav' => true,
                'nav_order' => 0,
            ],
            [
                'slug' => 'features',
                'title' => 'Features',
                'meta_title' => 'Features - Reco',
                'meta_description' => 'Explore all the powerful features Reco offers for your business.',
                'template' => 'default',
                'status' => 'published',
                'show_in_nav' => true,
                'nav_order' => 1,
            ],
            [
                'slug' => 'pricing',
                'title' => 'Pricing',
                'meta_title' => 'Pricing - Reco',
                'meta_description' => 'Simple, transparent pricing for every business size.',
                'template' => 'pricing',
                'status' => 'published',
                'show_in_nav' => true,
                'nav_order' => 2,
            ],
            [
                'slug' => 'faq',
                'title' => 'FAQ',
                'meta_title' => 'FAQ - Reco',
                'meta_description' => 'Frequently asked questions about Reco.',
                'template' => 'faq',
                'status' => 'published',
                'show_in_nav' => true,
                'nav_order' => 3,
            ],
            [
                'slug' => 'about',
                'title' => 'About Us',
                'meta_title' => 'About Us - Reco',
                'meta_description' => 'Learn about the team behind Reco.',
                'template' => 'default',
                'status' => 'published',
                'show_in_nav' => true,
                'nav_order' => 4,
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'meta_title' => 'Contact Us - Reco',
                'meta_description' => 'Get in touch with the Reco team.',
                'template' => 'default',
                'status' => 'published',
                'show_in_nav' => true,
                'nav_order' => 5,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy - Reco',
                'meta_description' => 'Reco privacy policy and data handling practices.',
                'template' => 'default',
                'status' => 'published',
                'show_in_nav' => false,
                'nav_order' => 10,
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms & Conditions',
                'meta_title' => 'Terms & Conditions - Reco',
                'meta_description' => 'Terms and conditions for using Reco.',
                'template' => 'default',
                'status' => 'published',
                'show_in_nav' => false,
                'nav_order' => 11,
            ],
        ];

        foreach ($pages as $page) {
            WebsitePage::firstOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
