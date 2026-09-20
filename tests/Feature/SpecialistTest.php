<?php

use App\Http\Controllers\EvaluationController;
use App\Models\Evaluation;
use App\Models\OlfactoryTaxonomy;
use App\Services\EvaluationService;
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
        'type' => 'fragrance',
    ]);

    $this->assertDatabaseHas('evaluation_tastes', [
        'evaluation_id' => $evaluation->id,
        'type' => 'aroma',
    ]);

    $this->assertDatabaseMissing('evaluation_tastes', [
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


    $evaluationId = $response->json('data.ulid');
    $evaluation = Evaluation::where('ulid', $evaluationId)->firstOrFail();

    //dump(['body' => $evaluationId, 'model_found' => $evaluation->ulid]);


    $uploadPayload = $payLoad;
    unset($uploadPayload['offeringId']);
    $uploadPayload = json_decode(json_encode($uploadPayload));

    $score = $response->json('data.cuppingScore');
    $aromaScore = $uploadPayload->affective->aroma->score - 2;
    $sweetnessScore = $uploadPayload->affective->sweetness->score;
    $sweetnessScore = $sweetnessScore === 9 ? $sweetnessScore - 1 : $sweetnessScore + 1;

    $uploadPayload->affective->aroma->score = $aromaScore;
    $uploadPayload->affective->sweetness->score = $sweetnessScore;
    $uploadPayload->affective->defects = null;
    $uploadPayload->descriptive->fragrance->note = 'intense fragance, not persistant in nose.';
    $uploadPayload->extrinsics->processing = 'desireable traces of fermentation, good control of variables.';

    $arrayPayload = json_decode(json_encode($uploadPayload), true);

    /*
    dump($user->hasRole('specialist', 'api'));   // lo que el middleware evalúa
    dump(auth('api')->check());                    // ¿hay user en el guard api?
    */

    $this->withToken($token)
        ->putJson("/evaluations/{$evaluationId}", $arrayPayload)
        ->assertOk()
        ->assertJsonPath('data.affective.aroma.score', $aromaScore)
        ->assertJsonPath('data.affective.sweetness.score', $sweetnessScore)
        ->assertJsonPath('data.isDefective', false)
        ->assertJsonPath('data.descriptive.fragrance.note', 'intense fragance, not persistant in nose.')
        ->assertJsonPath('data.extrinsics.processing', 'desireable traces of fermentation, good control of variables.')
        ->assertJsonPath('data.cuppingScore', fn($v) => $v !== $score);

    $this->assertDatabaseMissing('evaluation_tastes', [
        'evaluation_id' => $evaluation->id,
        'type' => 'defects',
    ]);
});


test('specialist_close_evaluation', function () {
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


    $evaluationId = $response->json('data.ulid');
    $evaluation = Evaluation::where('ulid', $evaluationId)->firstOrFail();

    $this->withToken($token)
        ->patchJson("/evaluations/{$evaluation->ulid}/close")
        ->assertOk();

    $this->assertDatabaseHas(
        'evaluations',
        [
            'id' => $evaluation->id,
            'status' => 'closed'
        ]
    );
});

test('specialist_cannot_update_closed_evaluation', function () {
    [$user, $token] = authenticate('specialist');

    $offering = createOfferingsForUser($user, 1, 1);

    $payLoad = createEvaluationPayload(
        offering: $offering,
        defectsCount: 2,
        notes: [
            'affective.fragrance' => 'intense fragance, persistant in nose.',
            'extrinsics.processing' => 'traces of fermentation, uncontrolled variables.',
        ]
    );

    $response = $this->withToken($token)
        ->postJson('/evaluations', $payLoad);

    $evaluationId = $response->json('data.ulid');
    $evaluation = Evaluation::where('ulid', $evaluationId)->firstOrFail();

    $this->withToken($token)
        ->patchJson("/evaluations/{$evaluation->ulid}/close")
        ->assertOk();

    $uploadPayload = $payLoad;
    unset($uploadPayload['offeringId']);
    $uploadPayload['descriptive']['fragrance']['note'] = 'trying to edit a closed one.';

    $this->withToken($token)
        ->putJson("/evaluations/{$evaluationId}", $uploadPayload)
        ->assertStatus(409);

    $this->assertDatabaseHas('evaluations', [
        'id' => $evaluation->id,
        'status' => 'closed',
    ]);
});

test('specialist_triggers_consensus', function () {
    [$owner] = authenticate('specialist');
    $offering = createOfferingsForUser($owner, 1, 1)->first();

    for ($i = 0; $i < 5; $i++) {
        [$evaluator, $token] = authenticate('specialist');
        $payLoad = createEvaluationPayload(offering: $offering);

        $ulid = $this->withToken($token)
            ->postJson('/evaluations', $payLoad)
            ->json('data.ulid');
        var_dump($ulid, $i);


        $this->withToken($token)
            ->patchJson("/evaluations/{$ulid}/close")
            ->assertOk();
    }

    $offering->refresh();
    $this->assertNotNull($offering->cupping_avg);
});
