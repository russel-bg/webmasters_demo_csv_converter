<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCsvFileRequest;
use App\Models\CsvFile;
use App\Services\CsvFileServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CsvFileController extends Controller
{
    public function __construct(
        private readonly CsvFileServiceInterface $csvFileService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $files = $this->csvFileService->listFilesForUser($user->id);
        return response()->json([
            'files' => $files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    'original_filename' => $file->original_filename,
                    'is_public' => $file->is_public,
                    'status' => $file->status,
                    'records_count' => $file->records_count,
                    'created_at' => $file->created_at->format('Y-m-d H:i:s'),
                    'author' => $file->is_public ? $file->user->name : null,
                    'can_download' => $file->isReady(),
                    'error_message' => $file->error_message,
                ];
            })
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCsvFileRequest $request): JsonResponse
    {
        $user = Auth::user();
        $data = [
            'user_id' => $user->id,
            'name' => $request->name,
            'is_public' => $request->boolean('is_public', false),
        ];
        $csvFile = $this->csvFileService->uploadCsvFile($data, $request->file('file'));
        Log::info('CSV file created successfully', [
            'file_id' => $csvFile->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'file_name' => $csvFile->name,
            'original_filename' => $csvFile->original_filename,
            'file_size' => $request->file('file')->getSize(),
            'is_public' => $csvFile->is_public,
            'storage_path' => $csvFile->file_path,
            'timestamp' => now()->toISOString(),
        ]);
        return response()->json([
            'message' => 'File uploaded successfully and queued for processing',
            'file_id' => $csvFile->id
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Download the JSON file.
     */
    public function download(CsvFile $csvFile): JsonResponse
    {
        $user = Auth::user();
        if (!$user->can('download', $csvFile)) {
            return response()->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }
        if (!$csvFile->isReady()) {
            return response()->json([
                'error' => 'File is not ready for download'
            ], Response::HTTP_BAD_REQUEST);
        }
        $jsonContent = $this->csvFileService->getJsonContent($csvFile);
        if ($jsonContent === null) {
            return response()->json([
                'error' => 'JSON file not found'
            ], Response::HTTP_NOT_FOUND);
        }
        Log::info('JSON file download requested', [
            'file_id' => $csvFile->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'file_name' => $csvFile->name,
            'json_path' => $csvFile->json_path,
            'timestamp' => now()->toISOString(),
        ]);
        return response()->json([
            'json' => $jsonContent,
            'filename' => $csvFile->name . '.json'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CsvFile $csvFile): JsonResponse
    {
        $user = Auth::user();
        if ($user->id !== $csvFile->user_id) {
            return response()->json([
                'error' => 'Unauthorized'
            ], Response::HTTP_FORBIDDEN);
        }
        Log::info('CSV file deleted', [
            'file_id' => $csvFile->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'file_name' => $csvFile->name,
            'timestamp' => now()->toISOString(),
        ]);
        $this->csvFileService->deleteCsvFile($csvFile);
        return response()->json([
            'message' => 'File deleted successfully'
        ]);
    }
}
