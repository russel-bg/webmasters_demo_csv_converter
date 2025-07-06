<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCsvFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $mimeType = $value->getMimeType();
                    
                    // Check file extension
                    if ($extension !== 'csv') {
                        $fail('The file must have a .csv extension.');
                        return;
                    }
                    
                    // Check MIME type - accept text/plain, text/csv, or application/csv
                    $allowedMimeTypes = ['text/plain', 'text/csv', 'application/csv'];
                    if (!in_array($mimeType, $allowedMimeTypes)) {
                        $fail("The file MIME type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $allowedMimeTypes));
                        return;
                    }
                    
                    // // Additional content validation for CSV structure
                    // $content = $value->get();
                    // $value->seek(0); // Reset file pointer
                    
                    // // Check if content contains commas and newlines (basic CSV structure)
                    // if (strpos($content, ',') === false || strpos($content, "\n") === false) {
                    //     $fail('The file does not appear to be a valid CSV file (missing comma separators or newlines).');
                    //     return;
                    // }
                    
                    // // Check if first line contains headers (has commas)
                    // $lines = explode("\n", $content);
                    // $firstLine = trim($lines[0]);
                    // if (strpos($firstLine, ',') === false) {
                    //     $fail('The file does not appear to be a valid CSV file (first line should contain headers separated by commas).');
                    //     return;
                    // }
                },
            ],
            'name' => 'required|string|max:255',
            'is_public' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    // Allow boolean values and string representations
                    $allowedValues = [true, false, 'true', 'false', '1', '0', 1, 0, '', null];
                    
                    if (!in_array($value, $allowedValues, true)) {
                        $fail('The is public field must be true or false.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a CSV file to upload.',
            'file.max' => 'The file size must not exceed 10MB.',
            'name.required' => 'Please provide a name for the document.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            
            // Check if user is registered for at least 10 days
            if ($user->created_at->diffInDays(now()) < 10) {
                $validator->errors()->add('user', 'You must be registered for at least 10 days to upload files.');
            }

            // Check if user has uploaded a file in the last 5 minutes
            $recentUpload = $user->csvFiles()
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($recentUpload) {
                $validator->errors()->add('file', 'You can only upload one file every 5 minutes.');
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string boolean values to actual booleans
        if ($this->has('is_public')) {
            $value = $this->input('is_public');
            
            if (is_string($value)) {
                $this->merge([
                    'is_public' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                ]);
            }
        }
    }
}
