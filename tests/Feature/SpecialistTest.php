<?php

use App\Models\Evaluation;
use App\Models\OlfactoryTaxonomy;
use Database\Seeders\CertificationTypeSeeder;
use Database\Seeders\GetAnOfferingSeeder;
use Database\Seeders\OlfactoryTaxonomySeeder;
use Database\Seeders\RolesSeeder;

beforeEach(
    function () {
        $this->seed(RolesSeeder::class);
        $this->seed(CertificationTypeSeeder::class);
        $this->seed(OlfactoryTaxonomySeeder::class);
        $this->seed(GetAnOfferingSeeder::class);
    }
);

test('specialist_create_evaluation', function () {
    [$user, $token] = authenticate('specialist');

    $offering = createOfferingsForUser($user, 1, 1);

    $payLoad = createEvaluationPayload(
        offering: $offering,
        defectsCount: 0
    );

    $response = $this->withToken($token)
        ->postJson('/evaluations', $payLoad);

    $response->assertJsonPath('data.offeringId', $offering->ulid)
        ->assertStatus(201);

    $evaluationId = $response->json('data.ulid');

    $evaluation = Evaluation::where('ulid', $evaluationId)->firstOrFail();


    $this->assertDatabaseHas('evaluation_tastes', [
        'evaluation_id' => $evaluation->id,
        'type' => 'defects',
    ]);
});

test('specialist_update_evaluation', function () {
    [$user, $token] = authenticate('specialist');

    $offering = createOfferingsForUser($user, 1, 1);


    $noteFragance = 'intense fragance, persistant in nose.';
    $noteProcessing = 'traces of fermentation, uncontrolled variables.';

    $payLoad = createEvaluationPayload(
        offering: $offering,
        defectsCount: 2,
        notes: [
            'affective.fragrance' => $noteFragance,
            'extrinsics.processing' => $noteProcessing,
        ]
    );



    $response = $this->withToken($token)
        ->postJson('/evaluations', $payLoad);

    $score = $response->json('data.cuppingScore');

    $evaluationId = $response->json('data.ulid');
    $evaluation = Evaluation::where('ulid', $evaluationId)->get();

    $uploadPayload = $payLoad;

    unset($uploadPayload['offeringId']);

    $uploadPayload = json_decode(json_encode($uploadPayload));

    $aromaScore = $uploadPayload->affective->aroma->score - 1;
    $sweetnessScore = $uploadPayload->affective->sweetness->score;

    $uploadPayload->affective->aroma->score = $aromaScore;
    $uploadPayload->affective->sweetness->score = $sweetnessScore === 9 ? $sweetnessScore - 2 : $sweetnessScore + 1;
    $sweetnessScore = $uploadPayload->affective->sweetness->score;

    $uploadPayload->affective->defects = null;
    $uploadPayload->descriptive->fragrance->note = 'intense fragance, not persistant in nose.';
    $uploadPayload->extrinsics->processing = 'desireable traces of fermentation, good control of variables.';

    $arrayPayload = json_decode(json_encode($uploadPayload), true);


    $this->withToken($token)
        ->putJson("/evaluations/{$evaluationId}", $arrayPayload)
        ->assertOk()
        ->assertJsonPath('data.affective.aroma.score', $aromaScore)
        ->assertJsonPath('data.affective.sweetness.score', $sweetnessScore)
        ->assertJsonPath('data.isDefective', false)
        ->assertJsonPath('data.descriptive.fragrance.note', 'intense fragance, not persistant in nose.')
        ->assertJsonPath('data.extrinsics.processing', 'desireable traces of fermentation, good control of variables.')
        ->asserJsonPath('data.cuppingScore', fn($v) => $v !== $score);

    $this->assertDatabaseMissing('evaluation_tastes', [
        'evaluation_id' => $evaluation->id,
        'type' => 'defects',
    ]);
});
