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
        $page = $this->page('home');
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
        $page = $this->page('features');
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.default', compact('page', 'navItems', 'settings'));
    }

    /**
     * Pricing page.
     */
    public function pricing()
    {
        $page = $this->page('pricing');
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
        $page = $this->page('faq');
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
        $page = $this->page('about');
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.default', compact('page', 'navItems', 'settings'));
    }

    /**
     * Contact page.
     */
    public function contact()
    {
        $page = $this->page('contact');
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
        $page = $this->page('privacy-policy');
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.default', compact('page', 'navItems', 'settings'));
    }

    /**
     * Terms & Conditions page.
     */
    public function terms()
    {
        $page = $this->page('terms');
        $navItems = $this->websiteService->getNavItems();
        $settings = $this->websiteService->getSiteSettings();

        return view('website.default', compact('page', 'navItems', 'settings'));
    }

    private function page(string $slug): WebsitePage
    {
        $page = $this->websiteService->getPage($slug);

        abort_if($page === null, 404);

        return $page;
    }
}
