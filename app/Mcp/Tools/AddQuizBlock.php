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

#[Description('Append a multiple choice quiz to a section, or insert it at a given block position. Each question is answered by picking exactly one option, so exactly one answer per question must be marked correct. Answers are shuffled for every learner, so do not write options that refer to their position.')]
class AddQuizBlock extends Tool
{
    use InsertsBlocks, ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'questions' => ['required', 'array', 'min:1', 'max:25'],
            'questions.*.question' => ['required', 'string', 'max:500'],
            'questions.*.image_url' => ['nullable', 'string', 'max:2048'],
            'questions.*.answers' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.answers.*.text' => ['required', 'string', 'max:300'],
            'questions.*.answers.*.correct' => ['required', 'boolean'],
            'position' => ['integer', 'min:0'],
        ]);

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        // The renderer resolves a pick with find(isCorrect), so a question with
        // none or several correct answers is silently unanswerable rather than
        // visibly broken. Refuse it here instead.
        foreach ($validated['questions'] as $index => $question) {
            $correct = count(array_filter($question['answers'], fn (array $a) => $a['correct']));

            if ($correct !== 1) {
                return Response::error(
                    'Question '.($index + 1)." has {$correct} correct answers; exactly one is required."
                );
            }
        }

        $questions = array_map(fn (array $question) => array_filter([
            'id' => (string) Str::uuid(),
            'question' => $question['question'],
            'imageUrl' => $question['image_url'] ?? null,
            'answers' => array_map(fn (array $answer) => [
                'id' => (string) Str::uuid(),
                'text' => $answer['text'],
                'isCorrect' => $answer['correct'],
            ], $question['answers']),
        ], fn ($value) => $value !== null), $validated['questions']);

        return $this->insertBlock($section, ['type' => 'quiz', 'data' => ['questions' => $questions]], $validated['position'] ?? null);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to add the quiz to.')->required(),
            'questions' => $schema->array()
                ->description('Questions, each with 2-6 answers and exactly one marked correct. Item shape: {"question": "...", "answers": [{"text": "...", "correct": true}], "image_url": "optional"}.')
                ->items($schema->object([
                    'question' => $schema->string()->description('The question text.')->required(),
                    'image_url' => $schema->string()->description('Optional image shown above the question.'),
                    'answers' => $schema->array()
                        ->description('2-6 options, exactly one with correct: true.')
                        ->items($schema->object([
                            'text' => $schema->string()->description('Answer text.')->required(),
                            'correct' => $schema->boolean()->description('Whether this is the right answer.')->required(),
                        ]))
                        ->required(),
                ]))
                ->required(),
            'position' => $this->positionSchema($schema),
        ];
    }
}
