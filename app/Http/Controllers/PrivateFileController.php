<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Support\Facades\Log;

class PrivateFileController extends Controller
{
    public function show(File $file)
    {
        Log::info('File model state', [
            'exists' => $file->exists,
            'attributes' => $file->getAttributes(),
            'route_param' => request()->route('file'),
        ]);

        $fullPath = storage_path('app/private/' . $file->path);

        Log::info('Resolved private file path', [
            'full_path' => $fullPath,
            'exists' => file_exists($fullPath),
        ]);

        if (!file_exists($fullPath)) {

            Log::error('Private file not found', [
                'file_id' => $file->id,
                'path' => $file->path,
                'full_path' => $fullPath,
            ]);

            abort(404, 'File not found: ' . $fullPath);
        }

        Log::info('Serving private file', [
            'file_id' => $file->id,
            'full_path' => $fullPath,
        ]);

        return response()->file($fullPath);
    }
}