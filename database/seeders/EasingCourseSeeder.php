<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Media;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EasingCourseSeeder extends Seeder
{
    private const TOPIC_SLUG = 'easing';

    private const GRAPHIC_SOURCE = 'resources/interactives/easing-simulator.html';

    private const GRAPHIC_NAME = 'easing-simulator.html';

    /**
     * Seed an example course built around the interactive easing simulator.
     *
     * Re-running replaces the course (chapters and sections cascade) but keeps
     * the uploaded graphic, so its URL stays stable across runs.
     */
    public function run(): void
    {
        $user = $this->resolveOwner();

        $category = Category::firstOrCreate(
            ['name' => 'Animation'],
            ['description' => 'Grundlagen der Bewegungsgestaltung']
        );

        $graphicUrl = $this->ensureGraphic();

        Topic::where('slug', self::TOPIC_SLUG)->delete();

        $topic = Topic::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Easing',
            'slug' => self::TOPIC_SLUG,
        ]);

        $this->chapter($topic, 'Grundlagen', 0, [
            ['Was ist Easing?', $this->sectionWhatIsEasing()],
            ['Die Easing-Kurve lesen', $this->sectionReadingTheCurve($graphicUrl)],
        ]);

        $this->chapter($topic, 'Die Standardkurven', 1, [
            ['Ease-In, Ease-Out, Ease-In-Out', $this->sectionStandardCurves()],
            ['Timing und Dauer', $this->sectionTiming()],
        ]);

        $this->chapter($topic, 'Wissenscheck', 2, [
            ['Quiz: Easing', $this->sectionQuiz()],
        ]);

        $this->command?->info("Kurs \"Easing\" angelegt: /themen/{$topic->slug}");
        $this->command?->info("Besitzer: {$user->email} (nur dieser sieht ihn unter /admin/topics)");
        $this->command?->info("Interaktive Grafik: {$graphicUrl}");
    }

    /**
     * The admin overview lists a user's own topics only, so the course has to
     * belong to whoever will edit it. Pass SEED_USER_EMAIL to pick that user;
     * without it the oldest account gets it, which on a fresh install is the
     * only one there is.
     */
    private function resolveOwner(): User
    {
        $email = env('SEED_USER_EMAIL');

        if ($email) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                throw new \RuntimeException("No user with email {$email}");
            }

            return $user;
        }

        return User::query()->oldest('id')->first()
            ?? User::factory()->create(['name' => 'MotionBase', 'email' => 'demo@motionbase.test']);
    }

    /**
     * Store the simulator on the private disk and register it in the media
     * library, reusing the existing record so the course keeps its URL.
     */
    private function ensureGraphic(): string
    {
        $source = base_path(self::GRAPHIC_SOURCE);

        if (! is_file($source)) {
            throw new \RuntimeException('Interactive graphic not found: ' . self::GRAPHIC_SOURCE);
        }

        $contents = file_get_contents($source);

        $media = Media::where('type', 'interactive')
            ->where('original_filename', self::GRAPHIC_NAME)
            ->first();

        if ($media) {
            Storage::disk('local')->put($media->path, $contents);
            $media->update(['size' => strlen($contents)]);

            return $media->url;
        }

        $filename = Str::uuid() . '.html';
        $path = 'interactive/' . $filename;

        Storage::disk('local')->put($path, $contents);

        $media = Media::create([
            'filename' => $filename,
            'original_filename' => self::GRAPHIC_NAME,
            'path' => $path,
            'url' => '',
            'mime_type' => 'text/html',
            'type' => 'interactive',
            'size' => strlen($contents),
        ]);

        $url = route('interactive.show', $media, absolute: false);
        $media->update(['url' => $url]);

        return $url;
    }

    /**
     * @param  array<int, array{0: string, 1: array<int, array<string, mixed>>}>  $sections
     */
    private function chapter(Topic $topic, string $title, int $order, array $sections): void
    {
        $chapter = Chapter::create([
            'topic_id' => $topic->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'is_published' => true,
            'sort_order' => $order,
        ]);

        foreach ($sections as $index => [$sectionTitle, $blocks]) {
            Section::create([
                'chapter_id' => $chapter->id,
                'title' => $sectionTitle,
                'slug' => Str::slug($sectionTitle),
                'is_published' => true,
                'sort_order' => $index,
                'content' => [
                    'time' => now()->getTimestampMs(),
                    'blocks' => $blocks,
                    'version' => '2.31.0',
                ],
            ]);
        }
    }

    // ---------------------------------------------------------------- blocks

    /** @return array<string, mixed> */
    private function header(string $text, int $level = 2): array
    {
        return ['type' => 'header', 'data' => ['text' => $text, 'level' => $level]];
    }

    /** @return array<string, mixed> */
    private function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'data' => ['text' => $text]];
    }

    /**
     * @param  array<int, string>  $items
     * @return array<string, mixed>
     */
    private function list(array $items, string $style = 'unordered'): array
    {
        return ['type' => 'list', 'data' => [
            'style' => $style,
            'items' => array_map(
                fn (string $item) => ['content' => $item, 'meta' => new \stdClass(), 'items' => []],
                $items
            ),
        ]];
    }

    /** @return array<string, mixed> */
    private function alert(string $type, string ...$paragraphs): array
    {
        return ['type' => 'alert', 'data' => [
            'type' => $type,
            'content' => strip_tags(implode(' ', $paragraphs)),
            'contentBlocks' => [
                'blocks' => array_map(fn (string $text) => $this->paragraph($text), $paragraphs),
            ],
        ]];
    }

    /** @return array<string, mixed> */
    private function code(string $code, string $language = 'css'): array
    {
        return ['type' => 'code', 'data' => ['code' => $code, 'language' => $language]];
    }

    /**
     * @param  array<int, string>  $head
     * @param  array<int, array<int, string>>  $rows
     * @return array<string, mixed>
     */
    private function table(array $head, array $rows): array
    {
        return ['type' => 'table', 'data' => [
            'withHeadings' => true,
            'content' => array_merge([$head], $rows),
        ]];
    }

    /** @return array<string, mixed> */
    private function interactive(string $url, string $caption, int $height = 640): array
    {
        return ['type' => 'interactive', 'data' => [
            'url' => $url,
            'caption' => $caption,
            'height' => $height,
        ]];
    }

    // -------------------------------------------------------------- sections

    /** @return array<int, array<string, mixed>> */
    private function sectionWhatIsEasing(): array
    {
        return [
            $this->paragraph('Ohne Easing legt ein Objekt jede Millisekunde exakt dieselbe Strecke zurück. Es steht, springt auf volle Geschwindigkeit, hält sie stur durch und bleibt schlagartig stehen. In der physischen Welt passiert das nie: Masse muss beschleunigt und wieder abgebremst werden.'),
            $this->paragraph('<b>Easing</b> beschreibt genau diese Verteilung. Technisch ist es eine Funktion, die den linearen Zeitfortschritt einer Animation – von 0 am Anfang bis 1 am Ende – auf einen Fortschrittswert abbildet. Die Animation dauert gleich lang; nur <i>wann</i> welcher Teil der Strecke zurückgelegt wird, ändert sich.'),
            $this->alert('info', 'Easing verändert nicht die Dauer einer Animation, sondern die Verteilung der Bewegung innerhalb dieser Dauer.'),
            $this->header('Warum das zählt', 3),
            $this->list([
                '<b>Glaubwürdigkeit</b> – Beschleunigung ist das, was unser Auge als „echt“ liest. Lineare Bewegung wirkt maschinell.',
                '<b>Wahrgenommene Geschwindigkeit</b> – Eine Animation mit schnellem Start fühlt sich reaktionsschneller an als eine lineare gleicher Dauer.',
                '<b>Hierarchie</b> – Wie etwas einsteigt, sagt, wie wichtig es ist. Ein sanftes Ausklingen ordnet sich unter, ein harter Stopp fordert Aufmerksamkeit.',
                '<b>Kontinuität</b> – Passende Kurven verbinden zwei Zustände, statt sie nur auszutauschen.',
            ]),
            $this->paragraph('Im nächsten Abschnitt schaust du dir an, wie sich diese Verteilung als Kurve darstellen lässt – und kannst sie selbst verbiegen.'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sectionReadingTheCurve(string $graphicUrl): array
    {
        return [
            $this->paragraph('Die übliche Darstellung ist ein Graph: auf der <b>X-Achse</b> läuft die Zeit von 0 bis 1, auf der <b>Y-Achse</b> der Fortschritt der Bewegung von 0 bis 1. Eine gerade Diagonale von links unten nach rechts oben bedeutet: in jedem Zeitabschnitt genau gleich viel Bewegung – also linear.'),
            $this->paragraph('Entscheidend ist die <b>Steigung</b>: Je steiler die Kurve an einer Stelle, desto schneller ist das Objekt in diesem Moment. Ein flacher Abschnitt heißt Stillstand oder Zeitlupe.'),
            $this->interactive($graphicUrl, ''),
            $this->header('Was die Kontrollpunkte tun', 3),
            $this->list([
                '<b>Kontrollpunkt 1</b> (dunkel) zieht die Tangente am Anfang. Nach rechts geschoben startet die Bewegung träge.',
                '<b>Kontrollpunkt 2</b> (pink) zieht die Tangente am Ende. Nach links geschoben klingt die Bewegung sanft aus.',
                'Ziehst du einen Punkt <b>über die obere Kante</b>, schießt die Kurve über den Zielwert hinaus – ein Overshoot. Das Objekt geht zu weit und kommt zurück.',
            ]),
            $this->alert(
                'neutral',
                'Probier den Unterschied zwischen <b>Ease-Out</b> und <b>Snap-In</b> aus: Beide sind am Anfang schnell, aber Snap-In kommt viel härter zur Ruhe.',
                'Ein Doppelklick auf einen Kontrollpunkt setzt ihn wieder auf die Diagonale zurück.'
            ),
            $this->paragraph('Diese vier Punkte – Start, zwei Kontrollpunkte, Ende – sind genau das, was eine <b>kubische Bézier-Kurve</b> ausmacht. Und genau die Notation nutzen CSS und die meisten Animationswerkzeuge.'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sectionStandardCurves(): array
    {
        return [
            $this->paragraph('Fast alles, was du im Alltag brauchst, sind vier Grundformen. Es lohnt sich, sie am Verhalten zu erkennen statt an ihren Zahlen.'),
            $this->header('Die vier Grundformen', 3),
            $this->table(
                ['Kurve', 'Verhalten', 'Wofür'],
                [
                    ['<b>Linear</b>', 'konstante Geschwindigkeit', 'Endlosschleifen: Spinner, Fortschrittsbalken. Falsch für alles, was Masse haben soll.'],
                    ['<b>Ease-Out</b>', 'schneller Start, sanftes Ausklingen', 'Alles was <i>erscheint</i>: Menüs, Dialoge, Tooltips. Reagiert sofort auf den Klick.'],
                    ['<b>Ease-In</b>', 'träger Start, schnelles Ende', 'Alles was <i>verschwindet</i>. Als Einstieg zäh, weil die Reaktion hinterherhinkt.'],
                    ['<b>Ease-In-Out</b>', 'sanft an beiden Enden', 'Bewegungen, die vollständig im Blick bleiben, etwa von A nach B.'],
                ],
            ),
            $this->header('In CSS notiert', 3),
            $this->paragraph('In CSS schreibst du sie als <code class="inline-code">cubic-bezier()</code> mit den beiden Kontrollpunkten aus dem Simulator:'),
            $this->code(<<<'CSS'
/* Die vier Grundformen als cubic-bezier */
.linear      { transition-timing-function: cubic-bezier(0,    0,    1,    1);    }
.ease-in     { transition-timing-function: cubic-bezier(0.42, 0,    1,    1);    }
.ease-out    { transition-timing-function: cubic-bezier(0,    0,    0.58, 1);    }
.ease-in-out { transition-timing-function: cubic-bezier(0.42, 0,    0.58, 1);    }

/* Overshoot: der zweite Wert darf über 1 hinausgehen */
.overshoot   { transition-timing-function: cubic-bezier(0.34, 1.56, 0.64, 1);    }

/* Praxisbeispiel: ein Dialog, der einsteigt */
.dialog {
    transition:
        opacity   160ms cubic-bezier(0, 0, 0.58, 1),
        transform 220ms cubic-bezier(0.34, 1.56, 0.64, 1);
}
CSS),
            $this->alert(
                'warning',
                '<b>Häufigster Fehler:</b> Ease-In für einsteigende Elemente. Die Bewegung beginnt fast unmerklich, und genau diese ersten 100 Millisekunden entscheiden, ob sich eine Oberfläche schnell anfühlt.',
                'Faustregel: rein mit Ease-Out, raus mit Ease-In.'
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sectionTiming(): array
    {
        return [
            $this->paragraph('Die beste Kurve rettet keine falsche Dauer. Beides zusammen ergibt erst das Timing – und die Dauer richtet sich danach, wie weit und wie groß sich etwas bewegt.'),
            $this->header('Wie lange darf es dauern?', 3),
            $this->list([
                '<b>100–150 ms</b> – kleine Zustandswechsel: Hover, Fokus, Checkbox. Soll unterhalb der bewussten Wahrnehmung bleiben.',
                '<b>200–300 ms</b> – der Normalfall: Dialoge, Dropdowns, Karten, Seitenwechsel innerhalb einer Ansicht.',
                '<b>300–500 ms</b> – große Flächen, die weite Strecken zurücklegen, etwa ein Vollbild-Overlay.',
                '<b>über 500 ms</b> – nur wenn die Bewegung selbst die Aussage ist. In einer Oberfläche fühlt sich das nach Warten an.',
            ]),
            $this->paragraph('Größere Distanz braucht mehr Zeit – aber nicht proportional. Verdoppelte Strecke heißt eher <b>1,3-fache</b> Dauer, nicht doppelte. Sonst wirkt die große Bewegung schwerfällig.'),
            $this->header('Versatz statt Gleichzeitigkeit', 3),
            $this->paragraph('Ein zweiter Hebel ist der <b>Versatz</b>: Wenn mehrere Elemente gleichzeitig einsteigen, wirkt das flach. Ein Abstand von 30–50 ms pro Element erzeugt Richtung und Rhythmus.'),
            $this->code(<<<'CSS'
/* Gestaffelter Einstieg einer Liste */
.list-item {
    animation: slide-in 240ms cubic-bezier(0, 0, 0.58, 1) both;
}

.list-item:nth-child(1) { animation-delay:   0ms; }
.list-item:nth-child(2) { animation-delay:  40ms; }
.list-item:nth-child(3) { animation-delay:  80ms; }
.list-item:nth-child(4) { animation-delay: 120ms; }

@keyframes slide-in {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0);   }
}
CSS),
            $this->alert(
                'info',
                'Respektiere <code class="inline-code">prefers-reduced-motion</code>. Für Menschen mit vestibulären Beschwerden sind große, schnelle Bewegungen keine Spielerei, sondern ein körperliches Problem.',
                'Die Animation ganz zu streichen ist selten die beste Antwort – meist reicht es, Distanz und Dauer zu reduzieren und beim Ein-/Ausblenden zu bleiben.'
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sectionQuiz(): array
    {
        return [
            $this->paragraph('Fünf Fragen zu dem, was du in diesem Kurs gesehen hast. Die Antworten werden bei jedem Durchlauf neu gemischt.'),
            $this->header('Fünf Fragen', 3),
            ['type' => 'quiz', 'data' => ['questions' => [
                $this->question('Was verändert Easing an einer Animation?', [
                    ['Die Verteilung der Bewegung innerhalb der Dauer', true],
                    ['Die Gesamtdauer der Animation', false],
                    ['Die zurückgelegte Distanz', false],
                    ['Die Bildrate der Wiedergabe', false],
                ]),
                $this->question('Ein Dialog soll sich beim Öffnen schnell anfühlen. Welche Kurve?', [
                    ['Ease-Out – schneller Start, sanftes Ausklingen', true],
                    ['Ease-In – träger Start, schnelles Ende', false],
                    ['Linear – konstante Geschwindigkeit', false],
                    ['Ease-In-Out – sanft an beiden Enden', false],
                ]),
                $this->question('Was bedeutet ein flacher Abschnitt in der Easing-Kurve?', [
                    ['Das Objekt bewegt sich in diesem Moment kaum', true],
                    ['Das Objekt bewegt sich besonders schnell', false],
                    ['Die Animation ist an dieser Stelle zu Ende', false],
                    ['Das Objekt bewegt sich rückwärts', false],
                ]),
                $this->question('Wie entsteht in cubic-bezier() ein Overshoot?', [
                    ['Ein Kontrollpunkt bekommt einen Y-Wert über 1', true],
                    ['Die Dauer wird über 500 ms gesetzt', false],
                    ['Beide Kontrollpunkte liegen auf der Diagonale', false],
                    ['Der X-Wert des ersten Punkts wird negativ', false],
                ]),
                $this->question('Für welchen Fall ist lineares Easing die richtige Wahl?', [
                    ['Für einen endlos rotierenden Ladeindikator', true],
                    ['Für ein Menü, das aufklappt', false],
                    ['Für eine Karte, die sich vergrößert', false],
                    ['Für ein Element, das ausgeblendet wird', false],
                ]),
            ]]],
            $this->alert('neutral', 'Danach lohnt sich der Rückweg zum <b>Simulator</b>: Stell dort jede der vier Grundformen ein und schau dir an, wie unterschiedlich der Ball ankommt.'),
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: bool}>  $answers
     * @return array<string, mixed>
     */
    private function question(string $question, array $answers): array
    {
        return [
            'id' => (string) Str::uuid(),
            'question' => $question,
            'answers' => array_map(fn (array $answer) => [
                'id' => (string) Str::uuid(),
                'text' => $answer[0],
                'isCorrect' => $answer[1],
            ], $answers),
        ];
    }
}
