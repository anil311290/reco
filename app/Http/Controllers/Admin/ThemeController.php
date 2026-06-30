<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    protected ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Display theme settings.
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $currentTheme = $this->themeService->getForCompany($companyId);
        $themes = $this->themeService->getAll();

        return view('admin.themes.index', compact('currentTheme', 'themes'));
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

        try {
            $companyId = auth()->user()->company_id;
            $theme = $this->themeService->getForCompany($companyId);
            $this->themeService->update($theme->id, $validated);

            return ResponseHelper::success($theme->fresh(), 'Theme updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Apply a predefined theme.
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme_id' => 'required|exists:themes,id',
        ]);

        try {
            $companyId = auth()->user()->company_id;
            $this->themeService->applyToCompany($validated['theme_id'], $companyId);
            return ResponseHelper::success(null, 'Theme applied successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Toggle dark mode.
     */
    public function toggleDarkMode(): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;
            $theme = $this->themeService->toggleDarkMode($companyId);
            return ResponseHelper::success($theme, 'Dark mode toggled');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get current theme as JSON (for AJAX).
     */
    public function current(): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $theme = $this->themeService->getForCompany($companyId);
        return ResponseHelper::success($theme);
    }
}
