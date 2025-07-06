<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsvFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'original_filename',
        'file_path',
        'json_path',
        'is_public',
        'records_count',
        'status',
        'error_message',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'records_count' => 'integer',
    ];

    /**
     * Get the user that owns the CSV file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the file is ready for download.
     */
    public function isReady(): bool
    {
        return $this->status === 'completed' && $this->json_path !== null;
    }

    /**
     * Check if the file processing failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the file is being processed.
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['queued', 'processing']);
    }
}
