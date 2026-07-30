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
                'content' => '<h2>Privacy Policy</h2><p>Reco collects only the information required to provide accounting, subscription, support, and security services. Business data remains associated with your company account and is protected through access controls and audit logging.</p><h3>Information We Process</h3><p>We process account details, company information, accounting records, device and login activity, and support communications supplied while using the service.</p><h3>How We Use Information</h3><p>Information is used to operate the platform, maintain security, provide support, process subscriptions, and improve product reliability. We do not sell personal or business data.</p><h3>Data Protection</h3><p>Access is restricted by authentication and role permissions. You are responsible for keeping login credentials secure and granting users only the permissions they require.</p><h3>Contact</h3><p>For privacy questions or data requests, contact support@reco.app.</p>',
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
                'content' => '<h2>Terms &amp; Conditions</h2><p>By creating a Reco account, you agree to use the service lawfully and to provide accurate registration and billing information.</p><h3>Account Responsibilities</h3><p>You are responsible for account security, user access, and the accuracy of financial records entered into the platform. Reco is an accounting tool and does not replace professional tax, legal, or financial advice.</p><h3>Subscriptions</h3><p>Plan limits, trial periods, charges, and renewal terms are shown during registration and in subscription settings. Continued use of paid features requires an active subscription.</p><h3>Acceptable Use</h3><p>You must not attempt unauthorized access, interfere with service operation, upload malicious content, or use the platform for unlawful activity.</p><h3>Availability and Liability</h3><p>We work to keep the service reliable and secure, but uninterrupted availability cannot be guaranteed. Liability is limited to the extent permitted by applicable law.</p>',
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
