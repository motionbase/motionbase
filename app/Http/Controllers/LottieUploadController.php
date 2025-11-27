<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LottieUploadController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'lottie' => 'required|file|max:10240', // 10MB max
            ]);

            $file = $request->file('lottie');
            
            if (!$file) {
                return response()->json([
                    'success' => 0,
                    'message' => 'No file uploaded',
                ], 400);
            }

            $originalExtension = $file->getClientOriginalExtension();
            
            // Only allow .json and .lottie files
            if (!in_array(strtolower($originalExtension), ['json', 'lottie'])) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Only .json and .lottie files are allowed',
                ], 422);
            }
            
            $filename = Str::uuid() . '.' . $originalExtension;
            $originalFilename = $file->getClientOriginalName();
            
            $path = $file->storeAs('editor-lottie', $filename, 'public');
            
            if (!$path) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Failed to store file',
                ], 500);
            }

            $url = Storage::url($path);

            // Save to media library
            $media = Media::create([
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'path' => $path,
                'url' => $url,
                'mime_type' => $originalExtension === 'lottie' ? 'application/zip' : 'application/json',
                'type' => 'lottie',
                'size' => $file->getSize(),
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
            Log::error('Lottie upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => 0,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
