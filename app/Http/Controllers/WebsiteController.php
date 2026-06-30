<?php

namespace App\Http\Controllers;

use App\Services\WebsiteService;
use App\Models\WebsitePage;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function __construct(
        protected WebsiteService $websiteService
    ) {}

    /**
     * Landing page.
     */
    public function home()
    {
        $page = $this->websiteService->getPage('home');
        $testimonials = $this->websiteService->getTestimonials(true);
        $plans = $this->websiteService->getPricingPlans();
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.home', compact(
            'page', 'testimonials', 'plans', 'navItems', 'settings'
        ));
    }

    /**
     * Features page.
     */
    public function features()
    {
        $page = $this->websiteService->getPage('features');
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.default', compact('page', 'navItems', 'settings'));
    }

    /**
     * Pricing page.
     */
    public function pricing()
    {
        $page = $this->websiteService->getPage('pricing');
        $plans = $this->websiteService->getPricingPlans();
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.pricing', compact('page', 'plans', 'navItems', 'settings'));
    }

    /**
     * FAQ page.
     */
    public function faq()
    {
        $page = $this->websiteService->getPage('faq');
        $faqs = $this->websiteService->getFaqs();
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.faq', compact('page', 'faqs', 'navItems', 'settings'));
    }

    /**
     * About page.
     */
    public function about()
    {
        $page = $this->websiteService->getPage('about');
        $testimonials = $this->websiteService->getTestimonials();
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.default', compact('page', 'navItems', 'settings'));
    }

    /**
     * Contact page.
     */
    public function contact()
    {
        $page = $this->websiteService->getPage('contact');
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.contact', compact('page', 'navItems', 'settings'));
    }

    /**
     * Submit contact form via AJAX.
     */
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $this->websiteService->submitContact($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for contacting us! We will get back to you shortly.',
        ]);
    }

    /**
     * Privacy Policy page.
     */
    public function privacy()
    {
        $page = $this->websiteService->getPage('privacy-policy');
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.default', compact('page', 'navItems', 'settings'));
    }

    /**
     * Terms & Conditions page.
     */
    public function terms()
    {
        $page = $this->websiteService->getPage('terms');
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.default', compact('page', 'navItems', 'settings'));
    }
}
