<?php

namespace App\Modules\Inspection\Services;

use App\Models\Inspection;
use App\Models\InspectionClosureRequest;
use App\Services\Storage\StorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ClosureRequestService
{
    public function __construct(
        protected StorageService $storageService
    ) {}

    public function create(
        Inspection $inspection,
        array $data,
        array $files,
        string $userId
    ): InspectionClosureRequest {

        Log::info('CLOSURE_REQUEST_START', [
            'inspection_id' => $inspection->id,
            'user_id'       => $userId,
            'files_count'   => is_countable($files) ? count($files) : 0,
        ]);

        if (!$inspection->id) {
            throw new RuntimeException('Inspection ID is NULL.');
        }

        if (empty($files)) {
            throw new RuntimeException('At least one evidence file is required.');
        }

        return DB::transaction(function () use (
            $inspection,
            $data,
            $files,
            $userId
        ) {

            Log::info('CLOSURE_REQUEST_TRANSACTION_START', [
                'inspection_id' => $inspection->id,
            ]);

            // =========================
            // 1. Prevent duplicates
            // =========================
            $hasPending = $inspection->closureRequests()
                ->where('status', 'pending')
                ->exists();

            if ($hasPending) {
                throw new RuntimeException(
                    'A pending closure request already exists.'
                );
            }

            // =========================
            // 2. Create closure request
            // =========================
            $closure = $inspection->closureRequests()->create([
                'message'      => $data['message'] ?? null,
                'status'       => 'pending',
                'requested_by' => $userId,
            ]);

            if (!$closure?->id) {
                throw new RuntimeException('Failed to create closure request.');
            }

            Log::info('CLOSURE_CREATED', [
                'closure_id' => $closure->id,
            ]);

            $uploadedFiles = [];

            try {

                // =========================
                // 3. Upload files
                // =========================
                foreach ($files as $index => $uploadedFile) {

                    if (!$uploadedFile) continue;

                    Log::info('UPLOADING_FILE', [
                        'index' => $index,
                    ]);

                    $file = $this->storageService->upload(
                        uploadedFile: $uploadedFile,
                        folder: 'closure-evidence',
                        uploadedBy: $userId,
                        category: 'closure_request',
                        visibility: 'public',

                        // polymorphic link
                        fileableType: InspectionClosureRequest::class,
                        fileableId: $closure->id
                    );

                    if (!$file?->id) {
                        throw new RuntimeException('File upload failed.');
                    }

                    $uploadedFiles[] = $file;

                    Log::info('FILE_UPLOADED', [
                        'file_id' => $file->id,
                    ]);
                }


            } catch (Throwable $e) {

                Log::error('CLOSURE_REQUEST_FAILED', [
                    'error' => $e->getMessage(),
                ]);

                foreach ($uploadedFiles as $file) {
                    try {
                        $this->storageService->delete($file);
                    } catch (Throwable $deleteError) {
                        Log::error('ROLLBACK_DELETE_FAILED', [
                            'file_id' => $file->id ?? null,
                            'error'   => $deleteError->getMessage(),
                        ]);
                    }
                }

                throw $e;
            }

            Log::info('CLOSURE_REQUEST_SUCCESS', [
                'closure_id' => $closure->id,
            ]);

            return $closure->load([
                'requestedBy',
                'attachments', // 👈 important (no .file unless nested model exists)
            ]);
        });
    }

    public function queryByScope($user, array $filters = [])
    {
        $query = \App\Models\InspectionClosureRequest::query()
            ->with([
                'inspection.business:id,name,city_id,subcity_id,wereda_id,registered_by',
                'inspection:id,inspection_number,business_id,inspector_id',
                'requestedBy:id,name',
                'reviewedBy:id,name',
                'attachments',
            ])
    
            /* =====================================================
                HARD SCOPE (SECURITY LAYER)
            ===================================================== */
            ->whereHas('inspection.business', function ($q) use ($user) {
    
                if (!empty($user->wereda_id)) {
                    $q->where('wereda_id', $user->wereda_id);
    
                } elseif (!empty($user->subcity_id)) {
                    $q->where('subcity_id', $user->subcity_id);
    
                } elseif (!empty($user->city_id)) {
                    $q->where('city_id', $user->city_id);
                }
            })
    
            ->latest();
    
        /* =====================================================
            OPTIONAL FILTERS (SAFE UX FILTERS ONLY)
        ===================================================== */
    
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    
        if (!empty($filters['inspection_id'])) {
            $query->where('inspection_id', $filters['inspection_id']);
        }
    
        if (!empty($filters['requested_by'])) {
            $query->where('requested_by', $filters['requested_by']);
        }
    
        \Log::info('CLOSURE_SCOPE_QUERY', [
            'user_id' => $user->id,
            'level' => $user->level ?? null,
            'filters' => $filters,
        ]);
    
        return $query;
    }

    public function decide(
        $closureRequest,
        string $status,
        string $reviewNote,
        string $userId
    ) {
        return \DB::transaction(function () use ($closureRequest, $status, $reviewNote, $userId) {
    
            // =========================
            // UPDATE CLOSURE REQUEST
            // =========================
            $closureRequest->update([
                'status' => $status,
                'review_note' => $reviewNote,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
            ]);
    
            // =========================
            // SYNC INSPECTION STATUS
            // =========================
            $inspection = $closureRequest->inspection;
    
            if ($status === 'approved') {
    
                $inspection->update([
                    'status' => 'ready_for_resolution',
                ]);
    
            } elseif ($status === 'rejected') {
    
                $inspection->update([
                    'status' => 'in_progress',
                ]);
            }
    
            return $closureRequest->fresh(['inspection']);
        });
    }
}