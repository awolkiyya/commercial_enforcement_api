<?php

namespace App\Services\Storage;

// use App\Jobs\OptimizeImageJob;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImageService extends StorageService
{
    /**
     * PROFILE IMAGE UPLOAD (optimized pipeline)
     */
    public function uploadProfileImage(
        UploadedFile $file,
        string $uploadedBy
        ): ?File {

        try {
            $uploaded = parent::upload(
                uploadedFile: $file,
                folder: 'users/profiles',
                uploadedBy: $uploadedBy,
                category: 'profile',
                visibility: 'public'
            );

            if (!$uploaded) {
                Log::error('Profile image upload failed (parent returned null)', [
                    'file_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $uploadedBy,
                ]);

                return null;
            }

            /**
             * Dispatch optimization asynchronously
             */
            // OptimizeImageJob::dispatch(
            //     $uploaded->disk,
            //     $uploaded->path
            // );

            return $uploaded->refresh();

        } catch (Throwable $e) {

            Log::error('Profile image upload exception', [
                'message' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'uploaded_by' => $uploadedBy,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * ORGANIZATION LOGO UPLOAD
     */
    public function uploadOrganizationLogo(
        UploadedFile $file,
        string $uploadedBy
        ): ?File {

        try {
            $uploaded = parent::upload(
                uploadedFile: $file,
                folder: 'organizations/logos',
                uploadedBy: $uploadedBy,
                category: 'organization_logo',
                visibility: 'public'
            );

            if (!$uploaded) {
                Log::error('Organization logo upload failed (parent returned null)', [
                    'file_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $uploadedBy,
                ]);

                return null;
            }

            OptimizeImageJob::dispatch(
                $uploaded->disk,
                $uploaded->path
            );

            return $uploaded->refresh();

        } catch (Throwable $e) {

            Log::error('Organization logo upload exception', [
                'message' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'uploaded_by' => $uploadedBy,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}