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
                'generalObservation' => null,
            ],
        ];
    }

    public function open(): static
    {
        return $this->state(fn() => ['status' => 'open']);
    }

    private const CATA_AXES = ['fragrance', 'aroma', 'flavor', 'aftertaste', 'mouthfeel'];

    /**
     * Marks the evaluation's tastes: 2 notes per sensory axis, 1 main taste and 1 defect,
     * chosen from a taste profile. Evaluations of one offering share its profile (see
     * tasteProfile), so their marks overlap as real cuppers' do; without one, each
     * evaluation draws its own.
     */
    public function withTastes(?array $profile = null): static
    {
        return $this->afterCreating(function ($evaluation) use ($profile) {
            $profile ??= self::tasteProfile();
            $rows = [];

            foreach (self::CATA_AXES as $axis) {
                foreach (collect($profile['aromatics'])->shuffle()->take(2) as $ref) {
                    $rows[] = ['taxonomy_ref' => $ref, 'type' => $axis];
                }
            }
            foreach (collect($profile['main_tastes'])->shuffle()->take(1) as $ref) {
                $rows[] = ['taxonomy_ref' => $ref, 'type' => 'main_tastes'];
            }
            foreach ($profile['defects'] as $ref) {
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

    /**
     * One offering's taste profile: 4 aromatic leaves shared by every sensory axis, 2 main
     * tastes and 1 defect. The seeder draws one per offering and passes it to withTastes.
     */
    public static function tasteProfile(): array
    {
        return [
            'aromatics'   => self::taxonomyRefs('aromatics', 4),
            'main_tastes' => self::taxonomyRefs('main_tastes', 2),
            'defects'     => self::taxonomyRefs('defects', 1),
        ];
    }

    private static function taxonomyRefs(string $category, int $count): array
    {
        $query = match ($category) {
            'mouthfeel' => OlfactoryTaxonomy::where('level', 1)->whereJsonContains('categories', 'mouthfeel'),
            'main_tastes' => OlfactoryTaxonomy::where('level', 0)->whereJsonContains('categories', 'main_tastes'),
            default => OlfactoryTaxonomy::doesntHave('children')->whereJsonContains('categories', $category),
        };

        return $query->inRandomOrder()->limit($count)->pluck('id')->all();
    }
}
