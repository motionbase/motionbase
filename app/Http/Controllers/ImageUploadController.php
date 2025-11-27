<?php

namespace App\Http\Controllers;

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
            
            $path = $file->storeAs('editor-images', $filename, 'public');
            
            if (!$path) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Failed to store file',
                ], 500);
            }
            
            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => Storage::url($path),
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
            
            Storage::disk('public')->put('editor-images/' . $filename, $contents);
            
            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => Storage::url('editor-images/' . $filename),
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

