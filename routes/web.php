<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CsvFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    Log::info('Welcome page visited');
    return view('welcome');
});

Route::get('/info', function () {
    Log::info('Phpinfo page visited');
    return phpinfo();
});

Route::get('/health', function () {
    $status = [];

    // Check Database Connection
    try {
        DB::connection()->getPdo();
        // Optionally, run a simple query
        DB::select('SELECT 1');
        $status['database'] = 'OK';
    } catch (\Exception $e) {
        $status['database'] = 'Error';
    }

    // Check Redis Connection
    try {
        Cache::store('redis')->put('health_check', 'OK', 10);
        $value = Cache::store('redis')->get('health_check');
        if ($value === 'OK') {
            $status['redis'] = 'OK';
        } else {
            $status['redis'] = 'Error';
        }
    } catch (\Exception $e) {
        $status['redis'] = 'Error';
    }

    // Check Storage Access
    try {
        $testFile = 'health_check.txt';
        Storage::put($testFile, 'OK');
        $content = Storage::get($testFile);
        Storage::delete($testFile);

        if ($content === 'OK') {
            $status['storage'] = 'OK';
        } else {
            $status['storage'] = 'Error';
        }
    } catch (\Exception $e) {
        $status['storage'] = 'Error';
    }

    // Determine overall health status
    $isHealthy = collect($status)->every(function ($value) {
        return $value === 'OK';
    });

    $httpStatus = $isHealthy ? 200 : 503;

    return response()->json($status, $httpStatus);
});

// API Routes
Route::prefix('api')->group(function () {
    // Auth routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth');
    
    // CSV File routes
    Route::middleware('auth')->group(function () {
        Route::get('/files', [CsvFileController::class, 'index']);
        Route::post('/files', [CsvFileController::class, 'store'])->middleware('log.file.uploads');
        Route::get('/files/{csvFile}/download', [CsvFileController::class, 'download']);
        Route::delete('/files/{csvFile}', [CsvFileController::class, 'destroy']);
    });
});

// SPA Route - serve Vue app for all other routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
