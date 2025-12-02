<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class HealthClaimDocument extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'health_claim_id',
        'document_type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    /**
     * Get the health claim that owns the document.
     */
    public function healthClaim(): BelongsTo
    {
        return $this->belongsTo(HealthClaim::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the document type label.
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'bill' => 'Medical Bill',
            'receipt' => 'Payment Receipt',
            'prescription' => 'Prescription',
            'medical_report' => 'Medical Report',
            'other' => 'Other Document',
            default => ucfirst($this->document_type),
        };
    }

    /**
     * Get the file size in human-readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the download URL for the document.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('health-claims.documents.download', $this->id);
    }

    /**
     * Delete the document file from storage.
     */
    public function deleteFile(): bool
    {
        if (Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }

        return true;
    }

    /**
     * Boot method to auto-delete file when model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            $document->deleteFile();
        });
    }
}
