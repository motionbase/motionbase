<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageUploadController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
            ]);

            $file = $request->file('image');
            
            if (!$file) {
                return response()->json([
                    'success' => 0,
                    'message' => 'No file uploaded',
                ], 400);
            }
            
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::uuid() . '.' . $extension;
            $originalFilename = $file->getClientOriginalName();
            
            $path = $file->storeAs('editor-images', $filename, 'public');
            
            if (!$path) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Failed to store file',
                ], 500);
            }

            $url = Storage::url($path);

            // Get image dimensions
            $width = null;
            $height = null;
            try {
                $imagePath = Storage::disk('public')->path($path);
                if (file_exists($imagePath)) {
                    $imageInfo = getimagesize($imagePath);
                    if ($imageInfo) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                }
            } catch (\Exception $e) {
                // Ignore dimension errors
            }

            // Save to media library
            $media = Media::create([
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'path' => $path,
                'url' => $url,
                'mime_type' => $file->getMimeType(),
                'type' => 'image',
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
            ]);
            
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
            Log::error('Image upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => 0,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function uploadByUrl(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $url = $request->input('url');
        
        try {
            $contents = file_get_contents($url);
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = Str::uuid() . '.' . $extension;
            $originalFilename = basename(parse_url($url, PHP_URL_PATH)) ?: 'image.' . $extension;
            
            $path = 'editor-images/' . $filename;
            Storage::disk('public')->put($path, $contents);
            
            $storedUrl = Storage::url($path);

            // Get image dimensions
            $width = null;
            $height = null;
            try {
                $imagePath = Storage::disk('public')->path($path);
                if (file_exists($imagePath)) {
                    $imageInfo = getimagesize($imagePath);
                    if ($imageInfo) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                }
            } catch (\Exception $e) {
                // Ignore dimension errors
            }

            // Save to media library
            $media = Media::create([
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'path' => $path,
                'url' => $storedUrl,
                'mime_type' => 'image/' . $extension,
                'type' => 'image',
                'size' => strlen($contents),
                'width' => $width,
                'height' => $height,
            ]);
            
            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $storedUrl,
                    'id' => $media->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'Could not fetch image from URL',
            ], 400);
        }
    }
}
