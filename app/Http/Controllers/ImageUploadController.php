<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageUploadController extends Controller
{
    private const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    /** @var array<string, string> mime type => safe file extension */
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

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
            
            // Derive the extension from the real file contents, never from the
            // client-supplied name - otherwise "photo.php" lands in a public,
            // PHP-executable directory.
            $extension = self::ALLOWED_IMAGE_MIMES[$file->getMimeType()] ?? null;

            if ($extension === null) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Dieser Dateityp wird nicht unterstützt.',
                ], 422);
            }

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
                'message' => 'Das Bild konnte nicht hochgeladen werden.',
            ], 500);
        }
    }

    public function uploadByUrl(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url'],
        ]);

        $url = $request->input('url');

        if (! $this->isSafeRemoteUrl($url)) {
            return response()->json([
                'success' => 0,
                'message' => 'Diese URL ist nicht erlaubt.',
            ], 422);
        }

        try {
            $response = Http::withOptions(['stream' => false])
                ->timeout(10)
                ->connectTimeout(5)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);

            if (! $response->successful()) {
                throw new \RuntimeException('Remote server returned '.$response->status());
            }

            $contents = $response->body();

            if (strlen($contents) > self::MAX_IMAGE_BYTES) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Das Bild ist zu gross (max. 5 MB).',
                ], 422);
            }

            // Never trust the URL extension or the remote Content-Type: derive the
            // real type from the bytes we actually received.
            $imageInfo = @getimagesizefromstring($contents);
            $mimeType = $imageInfo['mime'] ?? null;

            if (! $imageInfo || ! isset(self::ALLOWED_IMAGE_MIMES[$mimeType])) {
                return response()->json([
                    'success' => 0,
                    'message' => 'Die URL enthält kein unterstütztes Bild.',
                ], 422);
            }

            $extension = self::ALLOWED_IMAGE_MIMES[$mimeType];
            $filename = Str::uuid().'.'.$extension;
            $originalFilename = basename((string) parse_url($url, PHP_URL_PATH)) ?: 'image.'.$extension;

            $path = 'editor-images/'.$filename;
            Storage::disk('public')->put($path, $contents);

            $media = Media::create([
                'filename' => $filename,
                'original_filename' => $originalFilename,
                'path' => $path,
                'url' => Storage::url($path),
                'mime_type' => $mimeType,
                'type' => 'image',
                'size' => strlen($contents),
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null,
            ]);

            return response()->json([
                'success' => 1,
                'file' => [
                    'url' => $media->url,
                    'id' => $media->id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Image upload by URL failed', ['url' => $url, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => 0,
                'message' => 'Das Bild konnte von dieser URL nicht geladen werden.',
            ], 400);
        }
    }

    /**
     * Guard against SSRF: only plain http(s) to publicly routable hosts.
     */
    private function isSafeRemoteUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'];

        // Resolve every address the host points at - a single public A record is
        // not enough if the name also resolves to something internal.
        $addresses = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : array_merge(
                gethostbynamel($host) ?: [],
                array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'),
            );

        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            $isPublic = filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );

            if ($isPublic === false) {
                return false;
            }
        }

        return true;
    }
}
