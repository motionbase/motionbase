<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InsertsBlocks;
use App\Mcp\Concerns\ResolvesOwnedContent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a coloured callout to a section. Use it for something a reader must not miss - a common mistake, a constraint, a warning - not to emphasise ordinary prose.')]
class AddAlertBlock extends Tool
{
    use InsertsBlocks, ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:info,warning,danger,neutral'],
            'paragraphs' => ['required', 'array', 'min:1', 'max:5'],
            'paragraphs.*' => ['required', 'string', 'max:1000'],
            'position' => ['integer', 'min:0'],
        ]);

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        $paragraphs = array_map(
            fn (string $text) => ['type' => 'paragraph', 'data' => ['text' => $text]],
            $validated['paragraphs'],
        );

        return $this->insertBlock($section, ['type' => 'alert', 'data' => [
            'type' => $validated['type'],
            // Both are written on purpose: the renderers prefer contentBlocks
            // and fall back to content, and a block carrying only one of them
            // renders blank in the half that reads the other.
            'content' => Str::of(implode(' ', $validated['paragraphs']))->stripTags()->toString(),
            'contentBlocks' => ['blocks' => $paragraphs],
        ]], $validated['position'] ?? null);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to add the callout to.')->required(),
            'type' => $schema->string()
                ->description('info (context), warning (a mistake to avoid), danger (data loss or breakage), neutral (an aside).')
                ->required(),
            'paragraphs' => $schema->array()
                ->description('One to five paragraphs. Inline <b>, <i> and <code> are allowed.')
                ->items($schema->string())
                ->required(),
            'position' => $this->positionSchema($schema),
        ];
    }
}
