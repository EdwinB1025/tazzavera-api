<?php

namespace Database\Seeders;

use App\Events\EvaluationClosed;
use App\Models\Evaluation;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Database\Seeder;

class VerifiedOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * A sample of offerings, each with 5 to 9 closed specialist evaluations from distinct
     * specialists. EvaluationClosed is dispatched per offering, so the consensus listener
     * computes averages, concordance and the flavor tree and marks the offering verified.
     */
    public function run(int $sample = 15, int $specialists = 10): void
    {
        $evaluators = User::factory()->count($specialists)->assignRole('specialist')->create();

        Offering::inRandomOrder()->limit($sample)->get()->each(function (Offering $offering) use ($evaluators) {
            $evaluators->random(mt_rand(5, 9))->each(
                fn(User $specialist) => Evaluation::factory()->withTastes()->create([
                    'offering_id'  => $offering->id,
                    'evaluator_id' => $specialist->id,
                ])
            );

            event(new EvaluationClosed($offering->id));
        });
    }
}
