<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ThemeResource;
use App\Services\ThemeService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeApiController extends Controller
{
    protected ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Get current theme for the company.
     */
    public function current(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $theme = $this->themeService->getForCompany($companyId);

        return ResponseHelper::success(new ThemeResource($theme));
    }

    /**
     * Update theme settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'accent_color' => 'nullable|string|max:7',
            'sidebar_color' => 'nullable|string|max:7',
            'header_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'bg_color' => 'nullable|string|max:7',
            'font_family' => 'nullable|string|max:100',
            'dark_mode' => 'boolean',
            'logo_url' => 'nullable|string|max:500',
            'favicon_url' => 'nullable|string|max:500',
        ]);

        $companyId = $request->user()->company_id;
        $theme = $this->themeService->getForCompany($companyId);
        $this->themeService->update($theme->id, $validated);

        return ResponseHelper::success(new ThemeResource($theme->fresh()), 'Theme updated');
    }

    /**
     * Toggle dark mode.
     */
    public function toggleDarkMode(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $theme = $this->themeService->toggleDarkMode($companyId);

        return ResponseHelper::success(new ThemeResource($theme), 'Dark mode toggled');
    }

    /**
     * Get all available themes.
     */
    public function themes(): JsonResponse
    {
        $themes = $this->themeService->getAll();
        return ResponseHelper::success(ThemeResource::collection($themes));
    }

    /**
     * Apply a predefined theme.
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme_id' => 'required|exists:themes,id',
        ]);

        $companyId = $request->user()->company_id;
        $this->themeService->applyToCompany($validated['theme_id'], $companyId);

        return ResponseHelper::success(null, 'Theme applied');
    }
}
