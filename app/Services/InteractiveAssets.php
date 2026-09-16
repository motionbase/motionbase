<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Checks the files that travel with an interactive graphic - 3D models and
 * their textures - before a single one of them is stored.
 *
 * The rules follow from where the files end up: they are served from our own
 * origin. So the type is judged by the contents and never by the name, which
 * keeps a page from arriving as model.glb; and a model may only point at the
 * files uploaded beside it, which keeps it from making every learner's browser
 * call a third party.
 */
class InteractiveAssets
{
    // PHP drops every file past max_file_uploads (20 by default) without a
    // word, and the graphic itself takes one of those.
    public const MAX_FILES = 19;

    public const MAX_FILE_KB = 25 * 1024;

    // Stays well under post_max_size: past it PHP delivers an empty request.
    public const MAX_TOTAL_BYTES = 60 * 1024 * 1024;

    /** Extension => the content type the file is served with. */
    public const TYPES = [
        'glb' => 'model/gltf-binary',
        'gltf' => 'model/gltf+json',
        'bin' => 'application/octet-stream',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    // Flat names only: no slashes, nothing starting with a dot.
    public const NAME_PATTERN = '[A-Za-z0-9][A-Za-z0-9._-]{0,99}';

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{name: string, extension: string, mime: string, file: UploadedFile}>
     *
     * @throws ValidationException
     */
    public function check(array $files): array
    {
        $seen = [];
        $total = 0;

        foreach ($files as $file) {
            $name = $file->getClientOriginalName();
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (! preg_match('/^'.self::NAME_PATTERN.'$/', $name)) {
                $this->fail("„{$name}“: Dateinamen bitte nur aus Buchstaben, Ziffern, Punkt, Binde- und Unterstrich, ohne Leerzeichen.");
            }

            if (! isset(self::TYPES[$extension])) {
                $this->fail("„{$name}“ kann nicht mitgeladen werden. Erlaubt sind: ".implode(', ', array_keys(self::TYPES)).'.');
            }

            // Case-insensitive, because the author's own file system usually is:
            // Model.glb and model.glb would be one file there and two here.
            if (isset($seen[strtolower($name)])) {
                $this->fail("„{$name}“ ist doppelt ausgewählt.");
            }

            $seen[strtolower($name)] = true;
            $total += $file->getSize();
        }

        if ($total > self::MAX_TOTAL_BYTES) {
            $this->fail('Die Dateien sind zusammen größer als 60 MB.');
        }

        $siblings = array_map(fn (UploadedFile $file) => $file->getClientOriginalName(), $files);
        $checked = [];

        foreach ($files as $file) {
            $name = $file->getClientOriginalName();
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            match ($extension) {
                'glb' => $this->checkGlb($file, $name, $siblings),
                'gltf' => $this->checkGltfJson(json_decode((string) file_get_contents($file->getRealPath()), true), $name, $siblings),
                'png', 'jpg', 'jpeg', 'webp' => $this->checkImage($file, $name, self::TYPES[$extension]),
                // Opaque binary a .gltf points into. There is nothing to check
                // it against; what makes it harmless is how it is served.
                'bin' => null,
            };

            $checked[] = [
                'name' => $name,
                'extension' => $extension,
                'mime' => self::TYPES[$extension],
                'file' => $file,
            ];
        }

        return $checked;
    }

    /**
     * A model on its own, as uploaded to the media library. With nothing
     * uploaded beside it, it has to carry everything it needs.
     *
     * @throws ValidationException
     */
    public function checkModel(UploadedFile $file, string $name): void
    {
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'glb') {
            $this->fail("„{$name}“ ist keine .glb-Datei. In der Mediathek gehen nur .glb-Modelle – sie enthalten Geometrie und Texturen in einer Datei.");
        }

        $this->checkGlb($file, $name, []);
    }

    /**
     * Turn whatever an author named a file into a name a graphic can load it
     * by: "Mein Würfel (final).glb" becomes "Mein-Wuerfel-final.glb".
     */
    public static function safeName(string $name, string $extension): string
    {
        // German rules, so Würfel becomes Wuerfel rather than Wurfel
        $base = Str::ascii(pathinfo($name, PATHINFO_FILENAME), 'de');
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base);
        $base = trim(preg_replace('/-{2,}/', '-', $base), '.-_');
        $base = substr($base, 0, 100 - strlen($extension) - 1);

        return ($base !== '' ? $base : 'modell').'.'.$extension;
    }

    /**
     * A binary glTF: a 12 byte header, then a JSON chunk describing the scene.
     * Only the header and that chunk are read - the geometry after it can be
     * many megabytes and says nothing about whether the file is what it claims.
     */
    private function checkGlb(UploadedFile $file, string $name, array $siblings): void
    {
        $size = $file->getSize();
        $handle = fopen($file->getRealPath(), 'rb');

        try {
            $header = fread($handle, 20);

            if ($header === false || strlen($header) < 20) {
                $this->fail("„{$name}“ ist keine gültige GLB-Datei.");
            }

            $h = unpack('a4magic/Vversion/Vlength/VjsonLength/a4jsonType', $header);

            // The declared length has to match the file exactly, so nothing can
            // ride along after a well-formed model.
            if ($h['magic'] !== 'glTF'
                || $h['version'] !== 2
                || $h['length'] !== $size
                || $h['jsonType'] !== 'JSON'
                || $h['jsonLength'] === 0
                || $h['jsonLength'] > $size - 20) {
                $this->fail("„{$name}“ ist keine gültige GLB-Datei (glTF 2.0).");
            }

            $json = json_decode((string) stream_get_contents($handle, $h['jsonLength']), true);
        } finally {
            fclose($handle);
        }

        $this->checkGltfJson($json, $name, $siblings);
    }

    /**
     * glTF can point at further files - buffers and images - by uri. Those
     * load in the learner's browser, so each one has to be embedded or be one
     * of the files uploaded alongside.
     */
    private function checkGltfJson(mixed $json, string $name, array $siblings): void
    {
        if (! is_array($json) || ! str_starts_with((string) ($json['asset']['version'] ?? ''), '2.')) {
            $this->fail("„{$name}“ ist kein glTF-2.0-Modell.");
        }

        foreach (['buffers', 'images'] as $list) {
            foreach ((array) ($json[$list] ?? []) as $entry) {
                $uri = is_array($entry) ? ($entry['uri'] ?? null) : null;

                // No uri: the data sits in the GLB's binary chunk or a bufferView.
                if ($uri === null || (is_string($uri) && str_starts_with($uri, 'data:'))) {
                    continue;
                }

                if (! is_string($uri)) {
                    $this->fail("„{$name}“ enthält einen ungültigen Verweis.");
                }

                if (in_array(rawurldecode($uri), $siblings, true)) {
                    continue;
                }

                if (preg_match('#^([a-z][a-z0-9+.-]*:|//)#i', $uri)) {
                    $this->fail("„{$name}“ lädt „{$uri}“ von außerhalb. Modelle dürfen nur auf Dateien verweisen, die mit hochgeladen werden.");
                }

                $this->fail($siblings === []
                    ? "„{$name}“ lädt „{$uri}“ als eigene Datei nach. Für die Mediathek muss das Modell alles enthalten – beim Export als .glb die Texturen einbetten."
                    : "„{$name}“ verweist auf „{$uri}“, aber diese Datei ist nicht dabei. Unterordner gehen nicht – alle Dateien nebeneinander ablegen.");
            }
        }
    }

    private function checkImage(UploadedFile $file, string $name, string $expected): void
    {
        if ($file->getMimeType() !== $expected) {
            $this->fail("„{$name}“ ist kein echtes ".strtoupper(pathinfo($name, PATHINFO_EXTENSION)).'-Bild.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['assets' => $message]);
    }
}
