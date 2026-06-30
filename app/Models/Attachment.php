<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory, HasAuditFields, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'module_type',
        'module_id',
        'file_name',
        'original_name',
        'file_path',
        'file_disk',
        'file_size',
        'mime_type',
        'extension',
        'category',
        'description',
        'created_by',
        'created_by_ip',
        'version',
        'synced_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Polymorphic relationship to the owning module.
     * Usage: $attachment->module returns the related SalesInvoice, Company, etc.
     */
    public function module(): MorphTo
    {
        return $this->morphTo('module', 'module_type', 'module_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForModule($query, string $moduleType, int $moduleId = null)
    {
        $query->where('module_type', $moduleType);
        if ($moduleId) {
            $query->where('module_id', $moduleId);
        }
        return $query;
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->file_disk)->url($this->file_path);
    }

    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    // ── Methods ───────────────────────────────────────────────

    public function deleteFile(): bool
    {
        if (Storage::disk($this->file_disk)->exists($this->file_path)) {
            Storage::disk($this->file_disk)->delete($this->file_path);
        }
        return $this->delete();
    }
}
