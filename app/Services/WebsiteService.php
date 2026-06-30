<?php

namespace App\Services;

use App\Models\WebsitePage;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\PricingDisplay;
use App\Models\ContactSubmission;
use App\Models\SubscriptionPlan;
use App\Models\Setting;

class WebsiteService
{
    /**
     * Get published page by slug.
     */
    public function getPage(string $slug): ?WebsitePage
    {
        return WebsitePage::published()->where('slug', $slug)->first();
    }

    /**
     * Get navigation items.
     */
    public function getNavItems()
    {
        return WebsitePage::navItems()->get();
    }

    /**
     * Get active FAQs grouped by category.
     */
    public function getFaqs(): array
    {
        return Faq::active()->get()->groupBy('category')->toArray();
    }

    /**
     * Get active testimonials.
     */
    public function getTestimonials(bool $featuredOnly = false)
    {
        return $featuredOnly
            ? Testimonial::featured()->orderBy('sort_order')->get()
            : Testimonial::active()->orderBy('sort_order')->get();
    }

    /**
     * Get pricing plans with display config.
     */
    public function getPricingPlans()
    {
        return PricingDisplay::with('plan')
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($p) => $p->plan && $p->plan->is_active);
    }

    /**
     * Get subscription plans directly.
     */
    public function getSubscriptionPlans()
    {
        return SubscriptionPlan::where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Submit contact form.
     */
    public function submitContact(array $data): ContactSubmission
    {
        return ContactSubmission::create([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'created_by_ip' => request()->ip(),
        ]);
    }

    /**
     * Get site setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Get all public site settings.
     */
    public function getSiteSettings(): array
    {
        $keys = [
            'site_name', 'site_tagline', 'site_logo', 'site_favicon',
            'site_email', 'site_phone', 'site_address',
            'facebook_url', 'twitter_url', 'linkedin_url', 'instagram_url',
            'primary_color', 'secondary_color',
        ];

        return Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
    }

    /**
     * Clear website cache.
     */
    public function clearCache(): void
    {
        // No-op for now — caching disabled during development
    }
}
