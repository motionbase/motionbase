<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InsertsBlocks;
use App\Mcp\Concerns\ResolvesOwnedContent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Embed a YouTube video in a section. Accepts a watch, share, embed or shorts URL, or a bare video id.')]
class AddYoutubeBlock extends Tool
{
    use InsertsBlocks, ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'url' => ['required', 'string', 'max:2048'],
            'caption' => ['string', 'max:500'],
            'position' => ['integer', 'min:0'],
        ]);

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        $videoId = $this->extractVideoId($validated['url']);

        if (! $videoId) {
            // The renderers key on videoId and draw nothing without it, so a
            // URL we cannot read has to fail here rather than leave a blank.
            return Response::error("Could not read a video id from '{$validated['url']}'.");
        }

        return $this->insertBlock($section, ['type' => 'youtube', 'data' => [
            'url' => $validated['url'],
            'videoId' => $videoId,
            'caption' => $validated['caption'] ?? '',
        ]], $validated['position'] ?? null);
    }

    private function extractVideoId(string $url): ?string
    {
        $patterns = [
            '#youtube\.com/watch\?(?:.*&)?v=([\w-]{11})#i',
            '#youtube\.com/embed/([\w-]{11})#i',
            '#youtube\.com/shorts/([\w-]{11})#i',
            '#youtu\.be/([\w-]{11})#i',
            '#^([\w-]{11})$#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, trim($url), $match)) {
                return $match[1];
            }
        }

        return null;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to add the video to.')->required(),
            'url' => $schema->string()->description('YouTube URL or the 11 character video id.')->required(),
            'caption' => $schema->string()->description('Optional caption below the video.'),
            'position' => $this->positionSchema($schema),
        ];
    }
}
