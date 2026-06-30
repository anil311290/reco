<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Collection;

class ThemeService
{
    /**
     * Get all themes.
     */
    public function getAll(): Collection
    {
        return Theme::orderBy('is_default', 'desc')->orderBy('name')->get();
    }

    /**
     * Get theme for a company.
     */
    public function getForCompany(?int $companyId): Theme
    {
        return Theme::getForCompany($companyId);
    }

    /**
     * Get the default theme.
     */
    public function getDefault(): ?Theme
    {
        return Theme::where('is_default', true)->first();
    }

    /**
     * Create a new theme.
     */
    public function create(array $data): Theme
    {
        return Theme::create($data);
    }

    /**
     * Update a theme.
     */
    public function update(int $id, array $data): bool
    {
        $theme = Theme::findOrFail($id);
        return $theme->update($data);
    }

    /**
     * Apply theme to a company.
     */
    public function applyToCompany(int $themeId, int $companyId): void
    {
        // Deactivate current theme for company
        Theme::where('company_id', $companyId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Clone the theme for the company or update existing
        $theme = Theme::findOrFail($themeId);
        $existing = Theme::where('company_id', $companyId)->first();

        if ($existing) {
            $existing->update(array_merge(
                $theme->only([
                    'primary_color', 'secondary_color', 'accent_color',
                    'sidebar_color', 'header_color', 'text_color', 'bg_color',
                    'font_family', 'dark_mode', 'custom_css',
                ]),
                ['is_active' => true]
            ));
        } else {
            Theme::create(array_merge(
                $theme->only([
                    'name', 'primary_color', 'secondary_color', 'accent_color',
                    'sidebar_color', 'header_color', 'text_color', 'bg_color',
                    'font_family', 'dark_mode', 'custom_css',
                ]),
                [
                    'company_id' => $companyId,
                    'is_active' => true,
                    'is_default' => false,
                ]
            ));
        }
    }

    /**
     * Toggle dark mode for a company.
     */
    public function toggleDarkMode(int $companyId): Theme
    {
        $theme = $this->getForCompany($companyId);
        $theme->update(['dark_mode' => !$theme->dark_mode]);
        return $theme;
    }

    /**
     * Delete a theme (cannot delete default).
     */
    public function delete(int $id): bool
    {
        $theme = Theme::findOrFail($id);
        if ($theme->is_default) {
            return false;
        }
        return $theme->delete();
    }
}
