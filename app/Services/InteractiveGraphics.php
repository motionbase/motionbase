<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores an interactive graphic together with the files it loads.
 *
 * Shared by the editor upload and the MCP server, so a graphic comes out the
 * same whichever way it was made: on the private disk, reachable only through
 * the sandboxing routes, stored whole or not at all.
 */
class InteractiveGraphics
{
    /**
     * @param  UploadedFile|string  $html  the uploaded document, or its markup
     * @param  array<int, array{name: string, extension: string, mime: string, size: int, source: UploadedFile|string}>  $files
     *         source is an upload, or a path on the private disk to copy from
     */
    public function store(UploadedFile|string $html, string $originalFilename, array $files = []): Media
    {
        $disk = Storage::disk('local');
        $filename = Str::uuid().'.html';
        $stored = [];

        try {
            return DB::transaction(function () use ($disk, $html, $filename, $originalFilename, $files, &$stored) {
                $path = 'interactive/'.$filename;

                $written = $html instanceof UploadedFile
                    ? $html->storeAs('interactive', $filename, 'local')
                    : $disk->put($path, $html);

                if (! $written) {
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
                    'size' => $html instanceof UploadedFile ? $html->getSize() : strlen($html),
                ]);

                // Beside the graphic, under a uuid: the author's file name is
                // only ever a lookup key, never part of a path.
                $folder = 'interactive/'.pathinfo($filename, PATHINFO_FILENAME);

                foreach ($files as $file) {
                    $target = $folder.'/'.Str::uuid().'.'.$file['extension'];

                    $written = $file['source'] instanceof UploadedFile
                        ? $file['source']->storeAs($folder, basename($target), 'local')
                        : $disk->copy($file['source'], $target);

                    if (! $written) {
                        throw new \RuntimeException('Failed to store '.$file['name']);
                    }

                    $stored[] = $target;

                    $media->assets()->create([
                        'name' => $file['name'],
                        'path' => $target,
                        'mime_type' => $file['mime'],
                        'size' => $file['size'],
                    ]);
                }

                // A graphic with files is addressed one level down, so a
                // relative "model.glb" in it resolves next to the document.
                $url = $files
                    ? route('interactive.file', ['media' => $media, 'file' => 'index.html'], absolute: false)
                    : route('interactive.show', $media, absolute: false);

                $media->update(['url' => $url]);

                return $media;
            });
        } catch (\Throwable $e) {
            // All or nothing: a half stored graphic would be live and broken.
            $disk->delete($stored);

            throw $e;
        }
    }
}
