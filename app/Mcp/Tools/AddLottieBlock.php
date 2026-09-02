<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InsertsBlocks;
use App\Mcp\Concerns\ResolvesOwnedContent;
use App\Models\Media;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Place a Lottie animation from the media library in a section. Find its id with list_media; uploading is done in the web editor.')]
class AddLottieBlock extends Tool
{
    use InsertsBlocks, ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'media_id' => ['required', 'integer'],
            'caption' => ['string', 'max:500'],
            'loop' => ['boolean'],
            'autoplay' => ['boolean'],
            'position' => ['integer', 'min:0'],
        ]);

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        $media = Media::where('type', 'lottie')->find($validated['media_id']);

        if (! $media) {
            return Response::error("No Lottie file with id {$validated['media_id']} in the media library.");
        }

        return $this->insertBlock($section, ['type' => 'lottie', 'data' => [
            'url' => $media->url,
            'caption' => $validated['caption'] ?? '',
            'loop' => $validated['loop'] ?? true,
            'autoplay' => $validated['autoplay'] ?? true,
        ]], $validated['position'] ?? null);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to add the animation to.')->required(),
            'media_id' => $schema->integer()->description('Id from list_media, type lottie.')->required(),
            'caption' => $schema->string()->description('Optional caption below the animation.'),
            'loop' => $schema->boolean()->description('Repeat forever. Defaults to true.'),
            'autoplay' => $schema->boolean()->description('Start on its own. Defaults to true.'),
            'position' => $this->positionSchema($schema),
        ];
    }
}
