<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessCsvFile;
use App\Models\CsvFile;
use App\Repositories\CsvFileRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CsvFileService implements CsvFileServiceInterface
{
    public function __construct(
        private readonly CsvFileRepositoryInterface $csvFileRepository
    ) {}

    public function listFilesForUser(int $userId): Collection
    {
        return $this->csvFileRepository->findVisibleForUser($userId);
    }

    public function uploadCsvFile(array $data, UploadedFile $file): CsvFile
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = 'csv/' . $fileName;
        Storage::put($filePath, $file->get());
        $data['file_path'] = $filePath;
        $data['original_filename'] = $file->getClientOriginalName();
        $data['status'] = 'queued';
        $csvFile = $this->csvFileRepository->create($data);
        $this->dispatchProcessingJob($csvFile);
        return $csvFile;
    }

    public function deleteCsvFile(CsvFile $csvFile): void
    {
        if (Storage::exists($csvFile->file_path)) {
            Storage::delete($csvFile->file_path);
        }
        if ($csvFile->json_path && Storage::exists($csvFile->json_path)) {
            Storage::delete($csvFile->json_path);
        }
        $this->csvFileRepository->delete($csvFile);
    }

    public function dispatchProcessingJob(CsvFile $csvFile): void
    {
        ProcessCsvFile::dispatch($csvFile);
    }

    public function getJsonContent(CsvFile $csvFile): ?array
    {
        if (!$csvFile->json_path || !Storage::exists($csvFile->json_path)) {
            return null;
        }
        $json = Storage::get($csvFile->json_path);
        return json_decode($json, true);
    }
} 