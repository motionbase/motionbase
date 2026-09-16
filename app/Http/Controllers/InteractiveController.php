<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\InteractiveAssets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
    public function upload(Request $request, InteractiveAssets $assets): JsonResponse
    {
        try {
            $request->validate([
                'interactive' => 'required|file|max:5120', // 5MB max
                'assets' => 'array|max:'.InteractiveAssets::MAX_FILES,
                'assets.*' => 'file|max:'.InteractiveAssets::MAX_FILE_KB,
                'asset_count' => 'nullable|integer|min:0',
            ], [
                'assets.max' => 'Höchstens '.InteractiveAssets::MAX_FILES.' Dateien pro Grafik.',
                'assets.*.max' => 'Eine Datei ist größer als 25 MB.',
                'assets.*.file' => 'Eine Datei ist nicht vollständig angekommen.',
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

            $files = $request->file('assets', []);

            // PHP discards files past max_file_uploads without an error, so the
            // editor says how many it sent. A shortfall means the graphic would
            // go live missing parts of itself.
            if ($request->filled('asset_count') && (int) $request->input('asset_count') !== count($files)) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Es sind nicht alle Dateien angekommen ('.count($files).' von '.(int) $request->input('asset_count').').',
                ], 422);
            }

            // Every file is checked before the first one is stored.
            $checked = $assets->check($files);

            $filename = Str::uuid() . '.html';
            $originalFilename = $file->getClientOriginalName();
            $stored = [];

            try {
                $media = DB::transaction(function () use ($file, $filename, $originalFilename, $checked, &$stored) {
                    $path = $file->storeAs('interactive', $filename, 'local');

                    if (! $path) {
                        throw new \RuntimeException('Failed to store file');
                    }

                    $stored[] = $path;

                    $media = Media::create([
                        'filename' => $filename,
                        'original_filename' => $originalFilename,
                        'path' => $path,
                        'url' => '',
                        'mime_type' => 'text/html',
                        'type' => 'interactive',
                        'size' => $file->getSize(),
                    ]);

                    // Beside the graphic on the private disk, under a uuid: the
                    // author's file name is only ever a lookup key.
                    $folder = 'interactive/' . pathinfo($filename, PATHINFO_FILENAME);

                    foreach ($checked as $asset) {
                        $assetPath = $asset['file']->storeAs($folder, Str::uuid() . '.' . $asset['extension'], 'local');

                        if (! $assetPath) {
                            throw new \RuntimeException('Failed to store ' . $asset['name']);
                        }

                        $stored[] = $assetPath;

                        $media->assets()->create([
                            'name' => $asset['name'],
                            'path' => $assetPath,
                            'mime_type' => $asset['mime'],
                            'size' => $asset['file']->getSize(),
                        ]);
                    }

                    // A graphic with files is addressed one level down, so a
                    // relative "model.glb" in it resolves next to the document.
                    $url = $checked
                        ? route('interactive.file', ['media' => $media, 'file' => 'index.html'], absolute: false)
                        : route('interactive.show', $media, absolute: false);

                    $media->update(['url' => $url]);

                    return $media;
                });
            } catch (\Throwable $e) {
                // All or nothing: a half stored graphic would be live and broken.
                Storage::disk('local')->delete($stored);

                throw $e;
            }

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $media->url,
                    'id' => $media->id,
                    'assets' => array_column($checked, 'name'),
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

    /**
     * Anything below a graphic: the document itself as index.html, or a file
     * that was uploaded with it.
     */
    public function file(Request $request, Media $media, string $file): Response|BinaryFileResponse
    {
        return $file === 'index.html'
            ? $this->show($request, $media)
            : $this->asset($request, $media, $file);
    }

    /**
     * Serve a file uploaded together with a graphic.
     *
     * The graphic runs on an opaque origin, so every request it makes back to
     * us is cross-origin as far as the browser is concerned - hence
     * Access-Control-Allow-Origin. The other way out, allow-same-origin on the
     * frame, would hand uploaded markup the app's cookies. The wildcard is safe
     * because these files are as public as the graphic and no credentials
     * travel with them.
     *
     * Opened directly, the file must never act like a page on our origin: its
     * type is the one fixed from its contents at upload, sniffing is off, and a
     * sandbox without allow-scripts stops anything from running.
     */
    private function asset(Request $request, Media $media, string $name): BinaryFileResponse
    {
        abort_unless($media->type === 'interactive', 404);

        $asset = $media->assets()->where('name', $name)->first();
        $disk = Storage::disk('local');

        abort_unless($asset && $disk->exists($asset->path), 404);

        $response = response()->file($disk->path($asset->path), [
            'Content-Type' => $asset->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Access-Control-Allow-Origin' => '*',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
        ]);

        // Same revalidation as the document: a model replaced while an author
        // iterates has to show up on the next load.
        $response->setAutoLastModified();
        $response->setPublic();
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->setMaxAge(0);
        $response->isNotModified($request);

        return $response;
    }
}
