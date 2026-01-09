<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Storage;

class VaultController extends Controller
{
    /**
     * Download a secure blueprint file from the storage/app/vault folder.
     */
    public function download($lessonId)
    {
        // 1. Verify User is authenticated (Redundant if using auth middleware, but safe)
        if (!auth()->check()) {
            abort(403, 'Unauthorized access.');
        }

        // 2. Security: Verify Enrollment status via DB
        $enrolled = Enrollment::where('email', auth()->user()->email)
            ->where('status', 'paid')
            ->exists();

        // Allow Admins or Paid Students
        if (!$enrolled && !auth()->user()->is_admin) {
            abort(403, 'Subscription required to access the Snapshot Vault.');
        }

        // 3. Construct the absolute path
        // Files should be in: storage/app/vault/{lessonId}.json
        $path = storage_path("app/vault/{$lessonId}.json");

        // 4. Verify file existence on disk
        if (!file_exists($path)) {
            \Log::error("Vault Download Error: File not found at {$path}");
            abort(404, 'Blueprint file not found in the Factory Core.');
        }

        // 5. Serve the download using the response helper (Stops the IDE error)
        return response()->download($path, "automation-blueprint-{$lessonId}.json", [
            'Content-Type' => 'application/json',
        ]);
    }
}