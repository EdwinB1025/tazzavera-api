<?php

namespace Database\Factories;

use App\Models\Offering;
use App\Models\OlfactoryTaxonomy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EvaluationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'offering_id' => Offering::factory(),
            'evaluator_id' => User::factory(),
            'evaluation_type' => 'specialist',
            'status' => 'closed',
            'extraction_method' => 'v60',
            'cupping_score' => fake()->randomFloat(2, 70, 95),
            'is_defective' => fake()->boolean(20),
            'descriptive' => $this->descriptiveBlock(),
            'affective' => $this->affectiveBlock(),
            'extrinsics' => [
                'farming' => null,
                'processing' => null,
                'trading' => null,
                'certifications' => null,
                'general_observation' => null,
            ],
        ];
    }

    public function open(): static
    {
        return $this->state(fn() => ['status' => 'open']);
    }

    public function withTastes(): static
    {
        return $this->afterCreating(function ($evaluation) {
            $rows = [];

            foreach (['fragrance', 'aroma', 'flavor', 'aftertaste', 'mouthfeel'] as $axis) {
                foreach ($this->taxonomyRefs('aromatics', 2) as $ref) {
                    $rows[] = ['taxonomy_ref' => $ref, 'type' => $axis];
                }
            }
            foreach ($this->taxonomyRefs('main_tastes', 1) as $ref) {
                $rows[] = ['taxonomy_ref' => $ref, 'type' => 'main_tastes'];
            }
            foreach ($this->taxonomyRefs('defects', 1) as $ref) {
                $rows[] = ['taxonomy_ref' => $ref, 'type' => 'defects'];
            }

            collect($rows)
                ->unique(fn($r) => $r['taxonomy_ref'] . '|' . $r['type'])
                ->each(fn($row) => $evaluation->tastes()->create($row));
        });
    }

    private function affectiveBlock(): array
    {
        $axes = ['fragrance', 'aroma', 'flavor', 'aftertaste', 'acidity', 'sweetness', 'mouthfeel', 'overall'];
        $block = [];
        foreach ($axes as $axis) {
            $block[$axis] = ['score' => fake()->numberBetween(6, 9), 'note' => null];
        }
        return $block;
    }

    private function descriptiveBlock(): array
    {
        $axes = ['fragrance', 'aroma', 'flavor', 'aftertaste', 'acidity', 'sweetness', 'mouthfeel'];
        $block = [];
        foreach ($axes as $axis) {
            $block[$axis] = ['score' => fake()->numberBetween(3, 15), 'note' => null];
        }
        return $block;
    }

    private function taxonomyRefs(string $category, int $count): array
    {
        $query = match ($category) {
            'mouthfeel' => OlfactoryTaxonomy::where('level', 1)->whereJsonContains('categories', 'mouthfeel'),
            'main_tastes' => OlfactoryTaxonomy::where('level', 0)->whereJsonContains('categories', 'main_tastes'),
            default => OlfactoryTaxonomy::doesntHave('children')->whereJsonContains('categories', $category),
        };

        return $query->inRandomOrder()->limit($count)->pluck('id')->all();
    }
}
