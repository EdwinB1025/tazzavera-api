<?php

namespace Database\Seeders;

use App\Events\EvaluationClosed;
use App\Models\Evaluation;
use App\Models\Offering;
use App\Models\User;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Seeder;

class VerifiedOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * A sample of offerings, each with 5 to 9 closed specialist evaluations from distinct
     * specialists. The evaluations of one offering mark their tastes from one shared taste
     * profile, so the consensus holds a few agreed notes instead of the union of unrelated
     * random picks. EvaluationClosed is dispatched per offering, so the consensus listener
     * computes averages, concordance and the flavor tree and marks the offering verified.
     */
    public function run(int $sample = 15, int $specialists = 10): void
    {
        $evaluators = User::factory()->count($specialists)->assignRole('specialist')->create();

        Offering::inRandomOrder()->limit($sample)->get()->each(function (Offering $offering) use ($evaluators) {
            $profile = EvaluationFactory::tasteProfile();

            $evaluators->random(mt_rand(5, 9))->each(
                fn(User $specialist) => Evaluation::factory()->withTastes($profile)->create([
                    'offering_id'  => $offering->id,
                    'evaluator_id' => $specialist->id,
                ])
            );

            event(new EvaluationClosed($offering->id));
        });
    }
}
