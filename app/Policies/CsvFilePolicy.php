<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CsvFile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CsvFilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view the list of files
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CsvFile $csvFile): bool
    {
        // Users can view their own files or public files
        return $user->id === $csvFile->user_id || $csvFile->is_public;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check if user is registered for at least 10 days
        if ($user->created_at->diffInDays(now()) < 10) {
        return false;
        }

        // Check if user has uploaded a file in the last 5 minutes
        $recentUpload = $user->csvFiles()
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        return !$recentUpload;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CsvFile $csvFile): bool
    {
        return $user->id === $csvFile->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CsvFile $csvFile): bool
    {
        return $user->id === $csvFile->user_id;
    }

    /**
     * Determine whether the user can download the JSON file.
     */
    public function download(User $user, CsvFile $csvFile): bool
    {
        if (!$csvFile->isReady()) {
        return false;
    }

        return $user->id === $csvFile->user_id || $csvFile->is_public;
    }
}
