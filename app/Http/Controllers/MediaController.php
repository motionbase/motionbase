<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Get all media files with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        $query = Media::query()->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Search by filename
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('original_filename', 'like', '%' . $request->search . '%')
                  ->orWhere('alt', 'like', '%' . $request->search . '%');
            });
        }

        $media = $query->paginate($request->get('per_page', 24));

        return response()->json([
            'success' => true,
            'data' => $media->items(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    /**
     * Get a single media file
     */
    public function show(Media $media): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $media,
        ]);
    }

    /**
     * Update media metadata (alt text, etc.)
     */
    public function update(Request $request, Media $media): JsonResponse
    {
        $validated = $request->validate([
            'alt' => 'nullable|string|max:255',
        ]);

        $media->update($validated);

        return response()->json([
            'success' => true,
            'data' => $media,
        ]);
    }

    /**
     * Delete a media file
     */
    public function destroy(Media $media): JsonResponse
    {
        // Delete the file from storage
        if (Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully',
        ]);
    }
}

