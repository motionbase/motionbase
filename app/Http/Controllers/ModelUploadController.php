<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\InteractiveAssets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ModelUploadController extends Controller
{
    /**
     * Upload a 3D model to the media library.
     *
     * It is not served from here at all. A model reaches a learner only as a
     * file of an interactive graphic - copied in by the MCP server's
     * create_interactive - and from there through the sandboxed asset route.
     * So it goes to the private disk and keeps no public url.
     */
    public function upload(Request $request, InteractiveAssets $assets): JsonResponse
    {
        try {
            $request->validate([
                'model' => 'required|file|max:'.InteractiveAssets::MAX_FILE_KB,
            ], [
                'model.max' => 'Das Modell ist größer als 25 MB.',
            ]);

            $file = $request->file('model');
            $original = $file->getClientOriginalName();

            // Checked by contents: a glTF 2.0 header, and nothing loaded from
            // outside the file, since nothing is uploaded beside it.
            $assets->checkModel($file, $original);

            // The name a graphic will load it by, so it has to be a plain one.
            $name = InteractiveAssets::safeName($original, 'glb');
            $filename = Str::uuid().'.glb';
            $path = $file->storeAs('models', $filename, 'local');

            if (! $path) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Failed to store file',
                ], 500);
            }

            $media = Media::create([
                'filename' => $filename,
                'original_filename' => $name,
                'path' => $path,
                'url' => '',
                'mime_type' => 'model/gltf-binary',
                'type' => 'model',
                'size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => 1,
                'file' => [
                    'id' => $media->id,
                    'name' => $name,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => 0,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Model upload failed: '.$e->getMessage());

            return response()->json([
                'success' => 0,
                'message' => 'Upload failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
