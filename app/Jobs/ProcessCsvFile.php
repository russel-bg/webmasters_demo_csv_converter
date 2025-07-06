<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CsvFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCsvFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly CsvFile $csvFile
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting CSV file processing', [
            'file_id' => $this->csvFile->id,
            'file_name' => $this->csvFile->name,
            'timestamp' => now()->toISOString(),
        ]);

        try {
            $this->csvFile->update(['status' => 'processing']);

            $csvPath = $this->csvFile->file_path;
            $csvContent = Storage::get($csvPath);

            if (!$csvContent) {
                throw new \Exception('CSV file not found');
            }

            Log::info('CSV file content loaded', [
                'file_id' => $this->csvFile->id,
                'content_length' => strlen($csvContent),
                'timestamp' => now()->toISOString(),
            ]);

            $lines = explode("\n", trim($csvContent));
            $headers = str_getcsv(array_shift($lines));
            $records = [];

            foreach ($lines as $line) {
                if (empty(trim($line))) {
                    continue;
                }
                $values = str_getcsv($line);
                $record = array_combine($headers, $values);
                $records[] = $record;
            }

            Log::info('CSV parsing completed', [
                'file_id' => $this->csvFile->id,
                'records_count' => count($records),
                'headers' => $headers,
                'timestamp' => now()->toISOString(),
            ]);

            // Create hierarchical JSON structure
            $jsonData = $this->createHierarchicalStructure($records);

            // Save JSON file
            $jsonPath = 'json/' . $this->csvFile->id . '_' . time() . '.json';
            Storage::put($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT));

            Log::info('JSON file created successfully', [
                'file_id' => $this->csvFile->id,
                'json_path' => $jsonPath,
                'json_size' => Storage::size($jsonPath),
                'timestamp' => now()->toISOString(),
            ]);

            $this->csvFile->update([
                'status' => 'completed',
                'json_path' => $jsonPath,
                'records_count' => count($records),
            ]);

            Log::info('CSV file processing completed successfully', [
                'file_id' => $this->csvFile->id,
                'final_status' => 'completed',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('CSV file processing failed', [
                'file_id' => $this->csvFile->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString(),
            ]);

            $this->csvFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create hierarchical structure from flat records.
     */
    private function createHierarchicalStructure(array $records): array
    {
        if (empty($records)) {
            return [];
        }

        $headers = array_keys($records[0]);
        $hierarchy = [];

        foreach ($records as $record) {
            $current = &$hierarchy;
            
            foreach ($headers as $index => $header) {
                $value = $record[$header] ?? '';
                
                if ($index === count($headers) - 1) {
                    // Last level - add as leaf node
                    $current[] = [
                        'key' => $header,
                        'value' => $value,
                        'type' => 'leaf'
                    ];
                } else {
                    // Create or navigate to branch
                    $branchKey = $header . '_' . $value;
                    if (!isset($current[$branchKey])) {
                        $current[$branchKey] = [
                            'key' => $header,
                            'value' => $value,
                            'type' => 'branch',
                            'children' => []
                        ];
                    }
                    $current = &$current[$branchKey]['children'];
                }
            }
        }

        return $this->flattenHierarchy($hierarchy);
    }

    /**
     * Flatten hierarchy for better JSON structure.
     */
    private function flattenHierarchy(array $hierarchy): array
    {
        $result = [];
        
        foreach ($hierarchy as $item) {
            if ($item['type'] === 'leaf') {
                $result[] = $item;
            } else {
                $result[] = [
                    'key' => $item['key'],
                    'value' => $item['value'],
                    'type' => 'branch',
                    'children' => $this->flattenHierarchy($item['children'])
                ];
            }
        }
        
        return $result;
    }
}
