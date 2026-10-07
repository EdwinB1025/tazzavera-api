<?php

namespace Database\Factories;

use App\Models\CoffeeInventory;
use App\Models\Evaluation;
use App\Models\Location;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offering>
 */
class OfferingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $coffeeshop = User::role('coffeeshop')->whereHas('locations')->inRandomOrder()->first();
        $location = $coffeeshop->locations->random();
        $notAvailableIds = $location->offerings()->pluck('coffee_inventory_id');
        $coffeeInventoryId = CoffeeInventory::whereNotIn('id', $notAvailableIds)->inRandomOrder()->value('id');


        return [
            'coffee_inventory_id' => $coffeeInventoryId,
            'location_id' => $location->id,
            'evaluation_count' => 0,
            'defective_evaluation_count' => 0,
            'cupping_avg' => null,
            'fragrance_avg' => null,
            'aroma_avg' => null,
            'flavor_avg' => null,
            'aftertaste_avg' => null,
            'acidity_avg' => null,
            'sweetness_avg' => null,
            'mouthfeel_avg' => null,
            'overall_avg' => null,
            'concordance_affective' => null,
            'concordance_descriptive' => null,
            'verification_status' => 'provisional',
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($offering) {
            $owner = $offering->location->user;

            if (! $owner->hasRole('coffeeshop')) {
                return;
            }

            $offering->evaluations()->save(Evaluation::factory()->make([
                'offering_id'     => $offering->id,
                'evaluator_id'    => $owner->id,
                'evaluation_type' => 'baseline',
            ]));
        });
    }
}
