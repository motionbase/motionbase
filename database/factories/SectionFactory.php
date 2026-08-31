<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(2);

        return [
            'chapter_id' => Chapter::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'is_published' => true,
            'sort_order' => 0,
            'content' => [
                'time' => now()->getTimestampMs(),
                'blocks' => [
                    ['type' => 'paragraph', 'data' => ['text' => fake()->paragraph()]],
                ],
                'version' => '2.31.0',
            ],
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
