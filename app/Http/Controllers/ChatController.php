<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class ChatController extends Controller
{
    public function chat(Request $request, Topic $topic): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        // Load topic with all chapters and sections (only published)
        $topic->loadMissing([
            'chapters' => fn ($query) => $query
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->with([
                    'sections' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order'),
                ]),
        ]);

        // Extract content from topic
        $context = $this->extractTopicContent($topic);

        // If no content available, return error
        if (empty($context)) {
            return response()->json([
                'error' => 'Dieses Thema enthält noch keinen Inhalt.',
            ], 400);
        }

        // Create system prompt
        $systemPrompt = <<<PROMPT
Du bist ein freundlicher Lern-Assistent für das Thema "{$topic->title}".

WICHTIG: Du darfst NUR Informationen verwenden, die in folgendem Kontext enthalten sind. Wenn eine Frage nicht mit den bereitgestellten Informationen beantwortet werden kann, sage klar und freundlich: "Diese Information ist in diesem Kurs leider nicht enthalten. Ich kann nur Fragen zu den behandelten Themen beantworten."

Kontext:
{$context}

Antworte freundlich, präzise und pädagogisch. Verwende Markdown für Formatierung wenn sinnvoll. Beziehe dich auf spezifische Kapitel/Abschnitte wenn möglich.
PROMPT;

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $validated['question']],
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            $answer = $response->choices[0]->message->content ?? 'Entschuldigung, ich konnte keine Antwort generieren.';

            return response()->json([
                'answer' => $answer,
            ]);
        } catch (\Exception $e) {
            \Log::error('OpenAI API Error', [
                'topic_id' => $topic->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.',
            ], 500);
        }
    }

    /**
     * Extract all text content from a topic's chapters and sections
     */
    private function extractTopicContent(Topic $topic): string
    {
        $content = "# {$topic->title}\n\n";

        foreach ($topic->chapters as $chapterIndex => $chapter) {
            $content .= "## Kapitel " . ($chapterIndex + 1) . ": {$chapter->title}\n\n";

            foreach ($chapter->sections as $sectionIndex => $section) {
                $content .= "### Abschnitt " . ($sectionIndex + 1) . ": {$section->title}\n\n";

                // Extract text from Editor.js blocks
                $blocks = $section->content['blocks'] ?? [];
                foreach ($blocks as $block) {
                    $content .= $this->extractBlockText($block);
                }

                $content .= "\n\n";
            }
        }

        return $content;
    }

    /**
     * Extract text from an Editor.js block
     */
    private function extractBlockText(array $block): string
    {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];

        return match ($type) {
            'paragraph' => ($data['text'] ?? '') . "\n\n",
            'header' => ($data['text'] ?? '') . "\n\n",
            'list' => $this->extractListText($data['items'] ?? []) . "\n\n",
            'alert' => "💡 " . ($data['content'] ?? '') . "\n\n",
            'code' => "[Code-Beispiel: " . ($data['language'] ?? 'Code') . "]\n" . ($data['code'] ?? '') . "\n\n",
            'image' => "[Bild: " . ($data['caption'] ?? 'Abbildung') . "]\n\n",
            'youtube' => "[YouTube Video]\n\n",
            'lottie' => "[Animation]\n\n",
            'quiz' => "[Quiz mit " . count($data['questions'] ?? []) . " Fragen]\n\n",
            default => '',
        };
    }

    /**
     * Extract text from nested list items
     */
    private function extractListText(array $items): string
    {
        $text = '';

        foreach ($items as $item) {
            if (is_string($item)) {
                $text .= "- {$item}\n";
            } elseif (is_array($item)) {
                $content = $item['content'] ?? '';
                $text .= "- {$content}\n";

                // Handle nested items
                if (isset($item['items']) && is_array($item['items'])) {
                    $nestedText = $this->extractListText($item['items']);
                    $text .= preg_replace('/^/m', '  ', $nestedText);
                }
            }
        }

        return $text;
    }
}
