<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\WebsiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public marketing/legal content consumed by the mobile app (help, about,
 * privacy policy, terms) — no authentication required.
 */
class ContentApiController extends Controller
{
    public function __construct(private readonly WebsiteService $websiteService)
    {
    }

    public function faqs(): JsonResponse
    {
        return ResponseHelper::success($this->websiteService->getFaqs());
    }

    public function testimonials(Request $request): JsonResponse
    {
        return ResponseHelper::success(
            $this->websiteService->getTestimonials($request->boolean('featured'))
        );
    }

    public function siteSettings(): JsonResponse
    {
        return ResponseHelper::success($this->websiteService->getSiteSettings());
    }

    public function page(string $slug): JsonResponse
    {
        $page = $this->websiteService->getPage($slug);

        if (! $page) {
            return ResponseHelper::notFound('Page not found');
        }

        return ResponseHelper::success([
            'slug' => $page->slug,
            'title' => $page->title,
            'content' => $page->content,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'updated_at' => $page->updated_at?->toIso8601String(),
        ]);
    }

    public function submitContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $submission = $this->websiteService->submitContact($validated);

        return ResponseHelper::success(
            ['id' => $submission->id],
            'Thank you for contacting us. We will get back to you shortly.',
            201
        );
    }
}
