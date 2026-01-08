<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class VaultController extends Controller
{
    /**
     * Download a secure blueprint file
     */
    public function download($lessonId)
    {
        // 1. Verify User exists and is authenticated
        if (!auth()->check()) {
            abort(403, 'Unauthorized.');
        }

        // 2. Verify Enrollment status via DB
        $enrolled = Enrollment::where('email', auth()->user()->email)
            ->where('status', 'paid')
            ->exists();

        if (!$enrolled && !auth()->user()->is_admin) {
            abort(403, 'Subscription required to access the Vault.');
        }

        // 3. Define the path (Files stored in storage/app/vault/)
        $fileName = "vault/{$lessonId}.json";

        if (!Storage::disk('local')->exists($fileName)) {
            abort(404, 'Blueprint not found in the Factory Core.');
        }

        // 4. Serve the file
        return Storage::disk('local')->download($fileName, "builder-snapshot-{$lessonId}.json");
    }
}