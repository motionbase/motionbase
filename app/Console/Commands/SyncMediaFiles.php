<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncMediaFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes existing files in storage with the media database table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting media synchronization...');

        $disk = Storage::disk('public');
        $synced = 0;
        $skipped = 0;

        // Sync images
        $imagePath = 'editor-images';
        if ($disk->exists($imagePath)) {
            $images = $disk->files($imagePath);
            $this->info("Found " . count($images) . " image files");

            foreach ($images as $path) {
                if ($this->syncFile($path, 'image', $disk)) {
                    $synced++;
                } else {
                    $skipped++;
                }
            }
        }

        // Sync Lottie files
        $lottiePath = 'editor-lottie';
        if ($disk->exists($lottiePath)) {
            $lottieFiles = $disk->files($lottiePath);
            $this->info("Found " . count($lottieFiles) . " Lottie files");

            foreach ($lottieFiles as $path) {
                if ($this->syncFile($path, 'lottie', $disk)) {
                    $synced++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->info("Synchronization complete!");
        $this->info("Synced: {$synced} files");
        $this->info("Skipped: {$skipped} files (already exist)");

        return Command::SUCCESS;
    }

    /**
     * Sync a single file to the database
     */
    protected function syncFile(string $path, string $type, $disk): bool
    {
        // Check if file already exists in database
        $filename = basename($path);
        $exists = Media::where('path', $path)->exists();

        if ($exists) {
            return false;
        }

        try {
            $fullPath = $disk->path($path);
            $fileSize = filesize($fullPath);
            $url = Storage::url($path);

            // Get mime type
            $mimeType = mime_content_type($fullPath);
            if (!$mimeType) {
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $mimeType = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'json' => 'application/json',
                    'lottie' => 'application/zip',
                    default => 'application/octet-stream',
                };
            }

            // Get image dimensions if it's an image
            $width = null;
            $height = null;
            if ($type === 'image' && function_exists('getimagesize')) {
                $imageInfo = @getimagesize($fullPath);
                if ($imageInfo) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }

            Media::create([
                'filename' => $filename,
                'original_filename' => $filename,
                'path' => $path,
                'url' => $url,
                'mime_type' => $mimeType,
                'type' => $type,
                'size' => $fileSize,
                'width' => $width,
                'height' => $height,
            ]);

            $this->line("Synced: {$path}");
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to sync {$path}: " . $e->getMessage());
            return false;
        }
    }
}

