<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CsvFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CsvFileRepositoryInterface
{
    public function findById(int $id): ?CsvFile;
    public function findVisibleForUser(int $userId): Collection;
    public function create(array $data): CsvFile;
    public function delete(CsvFile $csvFile): void;
    public function update(CsvFile $csvFile, array $data): bool;
    public function getUserRecentUpload(int $userId, int $minutes): ?CsvFile;
} 