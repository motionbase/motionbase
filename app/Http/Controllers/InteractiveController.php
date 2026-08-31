<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InteractiveController extends Controller
{
    /**
     * Upload a self-contained interactive HTML graphic.
     *
     * The file is stored on the private disk - never on the web served public
     * disk - because it is attacker controlled markup as far as the browser is
     * concerned. It is handed out again only through show(), which pins it to
     * an opaque origin. Uploads are admin-only.
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'interactive' => 'required|file|max:5120', // 5MB max
            ]);

            $file = $request->file('interactive');

            if (! $file) {
                return response()->json([
                    'success' => 0,
                    'message' => 'No file uploaded',
                ], 400);
            }

            $originalExtension = strtolower($file->getClientOriginalExtension());

            // Only allow self-contained HTML documents
            if (! in_array($originalExtension, ['html', 'htm'])) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Only .html files are allowed',
                ], 422);
            }

            $filename = Str::uuid() . '.html';
            $originalFilename = $file->getClientOriginalName();

            $path = $file->storeAs('interactive', $filename, 'local');

            if (! $path) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Failed to store file',
                ], 500);
            }

            // Save to media library
            $media = Media::create([
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'path' => $path,
                'url' => '',
                'mime_type' => 'text/html',
                'type' => 'interactive',
                'size' => $file->getSize(),
            ]);

            // The graphic is reachable only through the sandboxing route
            $url = route('interactive.show', $media, absolute: false);
            $media->update(['url' => $url]);

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $url,
                    'id' => $media->id,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => 0,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Interactive upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => 0,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Serve an interactive graphic.
     *
     * The `sandbox` CSP directive puts the document on an opaque origin for
     * every way it can be reached - embedded in a course *and* opened directly
     * by URL - so its scripts can never touch the app's cookies, storage or
     * same-origin requests. The iframe sandbox attribute alone would only
     * cover the embedded case.
     */
    public function show(Request $request, Media $media): Response
    {
        abort_unless($media->type === 'interactive', 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($media->path), 404);

        $contents = $disk->get($media->path);

        $response = response($contents, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => 'sandbox allow-scripts',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Revalidate rather than cache blind: a graphic replaced in place has to
        // show up on the next reload, or authors debug against a stale copy for
        // an hour. The ETag keeps repeat views at a cheap 304.
        $response->setEtag(md5($contents));
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->setMaxAge(0);
        $response->isNotModified($request);

        return $response;
    }
}
