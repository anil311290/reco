<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
            'sidebar_color' => $this->sidebar_color,
            'header_color' => $this->header_color,
            'text_color' => $this->text_color,
            'bg_color' => $this->bg_color,
            'font_family' => $this->font_family,
            'logo_url' => $this->logo_url,
            'favicon_url' => $this->favicon_url,
            'login_bg_url' => $this->login_bg_url,
            'dark_mode' => $this->dark_mode,
            'custom_css' => $this->custom_css,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
