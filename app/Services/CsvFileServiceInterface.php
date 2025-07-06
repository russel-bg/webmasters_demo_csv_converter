<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CsvFile;
use Illuminate\Database\Eloquent\Collection;

interface CsvFileServiceInterface
{
    public function listFilesForUser(int $userId): Collection;
    public function uploadCsvFile(array $data, \Illuminate\Http\UploadedFile $file): CsvFile;
    public function deleteCsvFile(CsvFile $csvFile): void;
    public function dispatchProcessingJob(CsvFile $csvFile): void;
    /**
     * Get the JSON content for the given CsvFile, or null if not found.
     */
    public function getJsonContent(CsvFile $csvFile): ?array;
} 