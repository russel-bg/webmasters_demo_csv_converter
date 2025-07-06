<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CsvFile;
use Illuminate\Database\Eloquent\Collection;

class CsvFileRepository implements CsvFileRepositoryInterface
{
    public function findById(int $id): ?CsvFile
    {
        return CsvFile::find($id);
    }

    public function findVisibleForUser(int $userId): Collection
    {
        return CsvFile::with('user')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhere('is_public', true);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): CsvFile
    {
        return CsvFile::create($data);
    }

    public function delete(CsvFile $csvFile): void
    {
        $csvFile->delete();
    }

    public function update(CsvFile $csvFile, array $data): bool
    {
        return $csvFile->update($data);
    }

    public function getUserRecentUpload(int $userId, int $minutes): ?CsvFile
    {
        return CsvFile::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->latest('created_at')
            ->first();
    }
} 