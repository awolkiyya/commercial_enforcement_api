<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Models\File;
use Exception;

class StorageService
{
    protected string $disk;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'public');
    }

    // =====================================================
    // UPLOAD FILE (POLYMORPHIC SAFE)
    // =====================================================
    public function upload(
        UploadedFile $uploadedFile,
        string $folder,
        ?string $uploadedBy = null,
        string $category = 'general',
        string $visibility = 'public',
        ?string $fileableType = null,
        ?string $fileableId = null
    ): ?File {
        try {

            $extension = $uploadedFile->getClientOriginalExtension();
            $filename   = Str::uuid() . '.' . $extension;
            $datePath   = now()->format('Y/m/d');

            $path = $uploadedFile->storeAs(
                "{$folder}/{$datePath}",
                $filename,
                ['disk' => $this->disk]
            );

            $file = File::create([
                'disk'          => $this->disk,
                'path'          => $path,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_name'     => $filename,
                'mime_type'     => $uploadedFile->getMimeType(),
                'extension'     => $extension,
                'size'          => $uploadedFile->getSize(),
                'category'      => $category,
                'visibility'    => $visibility,
                'uploaded_by'   => $uploadedBy,

                // REQUIRED FOR uuidMorphs('fileable')
                'fileable_type' => $fileableType,
                'fileable_id'   => $fileableId,
            ]);

            if (!$file || !$file->id) {
                throw new Exception('File creation failed after upload.');
            }

            return $file;

        } catch (Exception $e) {
            report($e);
            return null;
        }
    }

    // =====================================================
    // DELETE FILE
    // =====================================================
    public function delete(?File $file): void
    {
        if (!$file) return;

        try {
            Storage::disk($file->disk)->delete($file->path);
            $file->delete();
        } catch (Exception $e) {
            report($e);
        }
    }

    // =====================================================
    // PUBLIC URL
    // =====================================================
    public function url(?File $file): ?string
    {
        if (!$file) return null;

        return $file->visibility === 'public'
            ? Storage::disk($file->disk)->url($file->path)
            : $this->temporaryUrl($file);
    }

    // =====================================================
    // PRIVATE SIGNED URL
    // =====================================================
    public function temporaryUrl(File $file, int $minutes = 10): ?string
    {
        return URL::temporarySignedRoute(
            'files.private',
            now()->addMinutes($minutes),
            ['path' => $file->path]
        );
    }

    // =====================================================
    // RAW STORAGE (BINARY SUPPORT)
    // =====================================================
    public function storeRaw(
        string $content,
        string $folder,
        string $filename,
        string $mimeType,
        string $extension,
        string $category = 'general',
        string $visibility = 'private',
        ?string $uploadedBy = null,
        ?string $fileableType = null,
        ?string $fileableId = null
    ): ?File {
        try {

            $datePath = now()->format('Y/m/d');
            $path = "{$folder}/{$datePath}/{$filename}";

            Storage::disk($this->disk)->put($path, $content);

            $file = File::create([
                'disk'          => $this->disk,
                'path'          => $path,
                'original_name' => $filename,
                'file_name'     => $filename,
                'mime_type'     => $mimeType,
                'extension'     => $extension,
                'size'          => Storage::disk($this->disk)->size($path),
                'category'      => $category,
                'visibility'    => $visibility,
                'uploaded_by'   => $uploadedBy,

                // REQUIRED FOR POLYMORPHIC RELATION
                'fileable_type' => $fileableType,
                'fileable_id'   => $fileableId,
            ]);

            if (!$file || !$file->id) {
                throw new Exception('Raw file creation failed.');
            }

            return $file;

        } catch (Exception $e) {
            report($e);
            return null;
        }
    }
}