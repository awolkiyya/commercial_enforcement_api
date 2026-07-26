<?php

namespace App\Modules\Inspection\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Inspection;
use App\Models\Complaint;
use App\Services\Storage\StorageService;

class ComplaintController extends Controller
{
    /**
     * =========================================
     * LIST COMPLAINTS FOR AN INSPECTION (PUBLIC)
     * =========================================
     */
    public function index(string $inspectionId)
    {
        $inspection = Inspection::with('complaints.files')
            ->findOrFail($inspectionId);

        return response()->json([
            'success' => true,
            'data' => $inspection->complaints
        ]);
    }

  /**
 * =========================================
 * CREATE NEW COMPLAINT (STRICT NO DUPLICATION)
 * =========================================
 */
public function store(
    Request $request,
    string $inspectionId,
    StorageService $storage
) {
    $inspection = Inspection::findOrFail($inspectionId);

    $validated = $request->validate([
        'reason' => ['required', 'string', 'min:10'],
        'files' => ['nullable', 'array'],
        'files.*' => ['file', 'max:10240']
    ]);

    return DB::transaction(function () use ($validated, $inspection, $request, $storage) {

        // =========================================
        // 1. BLOCK COMPLETED / CLOSED INSPECTIONS
        // =========================================
        if (in_array($inspection->status, ['completed', 'closed', 'resolved'])) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot submit a complaint for a completed inspection.',
            ], 403);
        }

        // =========================================
        // 2. BLOCK ONLY ACTIVE SUBMITTED COMPLAINT
        // =========================================
        $existing = Complaint::where('inspection_id', $inspection->id)
            ->where('status', 'submitted')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A complaint is already submitted for this inspection and is under review.',
                'data' => $existing->load('files')
            ], 409);
        }

        // =========================================
        // 3. CREATE COMPLAINT
        // =========================================
        $complaint = Complaint::create([
            'id' => (string) Str::uuid(),
            'inspection_id' => $inspection->id,
            'reason' => $validated['reason'],
            'status' => 'submitted',
        ]);

        // =========================================
        // 4. ATTACH FILES
        // =========================================
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {

                $storage->upload(
                    uploadedFile: $file,
                    folder: 'complaints',
                    uploadedBy: auth()->id(),
                    category: 'complaint_evidence',
                    visibility: 'public',
                    fileableType: $complaint::class,
                    fileableId: $complaint->id
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Complaint submitted successfully',
            'data' => $complaint->load('files')
        ], 201);
    });
}

    /**
     * =========================================
     * SHOW SINGLE COMPLAINT
     * =========================================
     */
    public function show(string $complaintId)
    {
        $complaint = Complaint::with('files', 'inspection')
            ->findOrFail($complaintId);

        return response()->json([
            'success' => true,
            'data' => $complaint
        ]);
    }
}