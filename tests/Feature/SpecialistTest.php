<?php

use App\Http\Controllers\EvaluationController;
use App\Models\Evaluation;
use App\Models\OlfactoryTaxonomy;
use App\Services\EvaluationService;
use App\Services\OfferingConsensusService;
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

    dump($payLoad);

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

test('specialist_triggers_consensus_calculation', function () {
    [$owner] = authenticate('specialist');
    $offering = createOfferingsForUser($owner, 1, 1)->first();

    Evaluation::factory()
        ->count(4)
        ->withTastes()
        ->create(['offering_id' => $offering->id]);

    [$evaluator, $token] = authenticate('specialist');
    $fifth = Evaluation::factory()
        ->withTastes()
        ->open()
        ->create([
            'offering_id' => $offering->id,
            'evaluator_id' => $evaluator->id,
        ]);

    $this->withToken($token)
        ->patchJson("/evaluations/{$fifth->ulid}/close")
        ->assertOk();

    $offering->refresh();

    //dump($offering->offeringTastes->toArray());

    $this->assertNotNull($offering->cupping_avg);
    $this->assertNotNull($offering->concordance_affective);
    $this->assertNotNull($offering->concordance_descriptive);
    $this->assertSame('verified', $offering->verification_status);
    $this->assertSame(5, $offering->evaluation_count);

    $this->assertDatabaseHas('axis_concordances', [
        'offering_id' => $offering->id,
        'cva_type' => 'affective',
    ]);

    $this->assertTrue($offering->offeringTastes()->exists());
});

test('specialist_deletes_evaluation', function () {
    [$user, $token] = authenticateWithWriteScope('specialist');

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
        ->deleteJson("/evaluations/{$evaluation->ulid}")
        ->assertOK();

    $this->assertDatabaseMissing('evaluations', ['id' => $evaluation->id]);
});

test('get_single_evaluation_by_ulid', function () {
    [$owner] = authenticate('specialist');
    $offering = createOfferingsForUser($owner, 1, 1)->first();

    $evaluation = \App\Models\Evaluation::factory()
        ->withTastes()
        ->create(['offering_id' => $offering->id]);

    $this->getJson("/evaluations/{$evaluation->ulid}")
        ->assertOk()
        ->assertJsonPath('data.ulid', $evaluation->ulid)
        ->assertJsonPath('data.status', $evaluation->status)
        ->assertJsonPath('data.evaluationType', $evaluation->evaluation_type);
});

test('filter_evaluations_by_query_parameters', function () {
    [$owner] = authenticate('specialist');
    $offering = createOfferingsForUser($owner, 1, 1)->first();

    $target = Evaluation::factory()
        ->withTastes()
        ->create([
            'offering_id' => $offering->id,
            'status' => 'closed',
            'cupping_score' => 85,
        ]);

    Evaluation::factory()
        ->withTastes()
        ->create([
            'offering_id' => $offering->id,
            'status' => 'open',
            'cupping_score' => 60,
        ]);

    $coffee = $offering->coffeeInventory->coffee;

    $response = $this->getJson('/evaluations?' . http_build_query([
        'coffeeId' => $coffee->ulid,
        'process' => $coffee->process,
        'status' => 'closed',
        'scoreMin' => 80,
        'scoreMax' => 90,
        'orderBy' => 'cupping_score',
        'orderDirection' => 'desc',
    ]));

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ulid', $target->ulid);
});

test('filter_evaluations_returns_multiple_locations', function () {
    [$owner] = authenticate('specialist');
    $offerings = createOfferingsForUser($owner, 1, 3);

    foreach ($offerings as $offering) {
        Evaluation::factory()
            ->withTastes()
            ->create([
                'offering_id' => $offering->id,
                'status' => 'closed',
                'cupping_score' => 85,
            ]);
    }

    $response = $this->getJson('/evaluations?' . http_build_query([
        'status' => 'closed',
    ]));

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});
