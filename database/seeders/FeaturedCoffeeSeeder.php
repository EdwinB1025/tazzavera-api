<?php

namespace Database\Seeders;

use App\Events\EvaluationClosed;
use App\Models\Coffee;
use App\Models\CoffeeInventory;
use App\Models\Evaluation;
use App\Models\Offering;
use App\Models\OlfactoryTaxonomy;
use App\Models\Roastery;
use App\Models\User;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Seeder;

class FeaturedCoffeeSeeder extends Seeder
{
    /**
     * EDB 10/13/26: two real Colombian coffees with their tasting notes.
     * Each one gets an inventory lot, an offering in every location of one coffee shop and
     * 5 to 9 closed specialist evaluations that mark its notes, so the consensus verifies it.
     * The notes use only the descriptors of the sensory taxonomy (name_en); the notes the
     * taxonomy lacks are left out: passion fruit, mango, watermelon and melon.
     * Safe to repeat: a coffee that already exists by name is skipped.
     */
    private const COFFEES = [
        [
            'coffee' => [
                'name'        => 'Enlu',
                'roast_level' => 'medium',
                'process'     => 'anaerobic',
                'variety'     => 'Castillo, Caturra',
                'country'     => 'Colombia',
                'region'      => 'Inzá, Cauca',
                'altitude'    => 1950,
            ],
            'aromatics'   => ['Pineapple', 'Honey', 'Winey'],
            'main_tastes' => ['Sweet', 'Sour/Acid'],
        ],
        [
            'coffee' => [
                'name'    => 'Opita Café',
                'process' => 'co_fermented',
                'variety' => 'Typica',
                'country' => 'Colombia',
                'region'  => 'Acevedo, Huila',
            ],
            'aromatics'   => ['Peach', 'Caramelized'],
            'main_tastes' => ['Sweet'],
        ],
    ];

    public function run(): void
    {
        $specialists = User::role('specialist')->get();

        if ($specialists->count() < 5) {
            $specialists = $specialists->concat(
                User::factory()->count(5 - $specialists->count())->assignRole('specialist')->create()
            );
        }

        foreach (self::COFFEES as $featured) {
            if (Coffee::where('name', $featured['coffee']['name'])->exists()) {
                continue;
            }

            $coffee = Coffee::create($featured['coffee']);

            $inventory = CoffeeInventory::create([
                'roastery_id'     => Roastery::inRandomOrder()->value('id'),
                'coffee_id'       => $coffee->id,
                'production_date' => now()->subDays(mt_rand(1, 30))->format('Y-m-d'),
                'roast_lot'       => now()->format('Y-m-d') . '-' . $coffee->id,
            ]);

            $coffeeshop = User::role('coffeeshop')->whereHas('locations')->with('locations')->inRandomOrder()->first();

            $profile = [
                'aromatics'   => $this->taxonomyRefs($featured['aromatics']),
                'mouthfeel'   => EvaluationFactory::tasteProfile()['mouthfeel'], // no body notes given: 2 drawn ones
                'main_tastes' => $this->taxonomyRefs($featured['main_tastes'], 0),
                'defects'     => [],
            ];

            foreach ($coffeeshop->locations as $location) {
                $offering = Offering::factory()->create([
                    'location_id'         => $location->id,
                    'coffee_inventory_id' => $inventory->id,
                ]);

                $specialists->random(min(mt_rand(5, 9), $specialists->count()))->each(
                    fn(User $specialist) => Evaluation::factory()->withTastes($profile)->create([
                        'offering_id'   => $offering->id,
                        'evaluator_id'  => $specialist->id,
                        'is_defective'  => false,
                        'cupping_score' => mt_rand(8400, 8900) / 100,
                    ])
                );

                event(new EvaluationClosed($offering->id));
            }
        }
    }

    /**
     * The taxonomy ids of the named descriptors; aromatics are leaves, main tastes level 0.
     */
    private function taxonomyRefs(array $names, ?int $level = null): array
    {
        return OlfactoryTaxonomy::whereIn('name_en', $names)
            ->when($level !== null, fn($q) => $q->where('level', $level))
            ->pluck('id')
            ->all();
    }
}
