<?php

namespace App\Http\Controllers;

use App\Contracts\EvaluationServiceContract;
use App\Http\Requests\FilterUserEvaluationRequest;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\UpdateEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\ResponseField;

/**EDB 10/06/26: Evaluations of the authenticated specialist: own reads and every write. The public reads stay in EvaluationController */
class UserEvaluationController extends Controller
{

    public function __construct(private EvaluationServiceContract $service) {}

    /**
     * List my evaluations
     *
     * Returns a paginated list of the evaluations created by the authenticated
     * specialist, each including its taste notes (olfactory taxonomy references)
     * and the offering it belongs to. Supports the same filtering and sorting as
     * the public list, except the evaluator, which is always the authenticated
     * user.
     *
     * **Authorization:** requires the `specialist` role and the `profile:read`
     * or `profile:write` scope.
     *
     * @group Evaluations
     *
     * @authenticated
     *
     * @responseFile storage/scribe/responses/userevaluations.index.json
     */
    #[ResponseField('data.offeringId', 'string', "The ULID of the evaluated offering. `null` when the offering was deleted: specialists' evaluations are kept without an offering.", nullable: true)]
    public function index(FilterUserEvaluationRequest $request)
    {
        $evaluations = $request->user()->evaluations()
            ->filter($request->validated())
            ->with('tastes.taxonomy:id,ulid', 'offering:id,ulid')
            ->paginate();

        return EvaluationResource::collection($evaluations);
    }

    /**
     * Get my evaluation
     *
     * Returns a single evaluation of the authenticated specialist with its taste
     * notes and associated offering. An evaluation that does not exist, or that
     * belongs to another user, returns `404 Not Found`, so the response never
     * reveals whether another user's evaluation exists.
     *
     * **Authorization:** requires the `specialist` role and the `profile:read`
     * or `profile:write` scope.
     *
     * @group Evaluations
     *
     * @authenticated
     *
     * @urlParam evaluation_ulid string required The ULID of the evaluation. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @response 404 scenario="Not found or not the owner" {"message": "No query results for model [App\\Models\\Evaluation]."}
     *
     * @responseFile storage/scribe/responses/userevaluations.show.json
     */
    #[ResponseField('data.offeringId', 'string', "The ULID of the evaluated offering. `null` when the offering was deleted: specialists' evaluations are kept without an offering.", nullable: true)]
    public function show(Request $request, string $evaluation)
    {
        //EDB 10/06/26: resolved through the user's relation, not route model binding, so another user's evaluation is a 404 with the same message as a missing one.
        $evaluation = $request->user()->evaluations()
            ->where('ulid', $evaluation)
            ->with('tastes.taxonomy:id,ulid', 'offering:id,ulid')
            ->firstOrFail();

        return new EvaluationResource($evaluation);
    }

    /**
     * Create an evaluation
     *
     * Records a new sensory evaluation (cupping) for an offering. The evaluation
     * is created in the "open" state and must later be finalized via the close
     * endpoint. Returns the created evaluation with a confirmation message.
     *
     * The request body carries three main blocks: `descriptive` (objective
     * assessment, scores 0–15), `affective` (preference assessment, scores 1–9),
     * and `extrinsics` (contextual metadata). See the body parameters and example
     * below for the full nested structure.
     *
     * **Authorization:** requires the `specialist` role.
     *
     * @group Evaluations
     *
     * @authenticated
     *
     * @responseFile 201 storage/scribe/responses/evaluations.store.json
     */
    #[ResponseField('data.offeringId', 'string', "The ULID of the evaluated offering. `null` when the offering was deleted: specialists' evaluations are kept without an offering.", nullable: true)]
    public function store(StoreEvaluationRequest $request)
    {
        $this->service->parseEvaluation($request);
        $this->service->saveNewEvaluation();

        return (new EvaluationResource($this->service->getEvaluation()))
            ->additional(['message' => __('evaluations.created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an evaluation
     *
     * Updates an existing evaluation. Returns the updated evaluation with a
     * confirmation message. The body structure matches the create endpoint,
     * except `offeringId` is prohibited — an evaluation cannot be reassigned to
     * another offering.
     *
     * A **closed** evaluation cannot be updated; attempting to do so returns
     * `409 Conflict`.
     *
     * **Authorization:** requires the `specialist` role and permission to update
     * the evaluation (enforced by the evaluation policy). A specialist who does
     * not own the evaluation receives `403 Forbidden`.
     *
     * @group Evaluations
     *
     * @authenticated
     *
     * @urlParam evaluation_ulid string required The ULID of the evaluation. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @response 403 scenario="Not the owner" {"message": "One or more evaluations do not belong to the user."}
     * @response 409 scenario="Evaluation already closed" {"message": "Evaluation cannot be updated."}
     *
     * @responseFile storage/scribe/responses/evaluations.update.json
     */
    #[ResponseField('data.offeringId', 'string', "The ULID of the evaluated offering. `null` when the offering was deleted: specialists' evaluations are kept without an offering.", nullable: true)]
    public function update(UpdateEvaluationRequest $request, Evaluation $evaluation)
    {
        abort_if($evaluation->status === 'closed', 409, __('evaluations.status_closed'));
        $this->service->parseEvaluation($request);
        $this->service->updateEvaluation();

        return (new EvaluationResource($this->service->getEvaluation()))
            ->additional(['message' => __('evaluations.updated')])
            ->response();
    }

    /**
     * Close an evaluation
     *
     * Transitions an evaluation to the **closed** state, finalizing it. A closed
     * evaluation can no longer be updated. No request body is required — the
     * evaluation is validated and closed using the data already stored.
     *
     * The evaluation is only closed if it is complete. Completeness requires all
     * 15 sensory scores greater than 0 (7 descriptive + 8 affective), plus at
     * least one taste reference for each of: main tastes, mouthfeel, and the
     * aromatic set (fragrance/aroma/flavor/aftertaste). If any is missing, the
     * request fails with `409 Conflict`.
     *
     * If the evaluation is **already closed**, the endpoint returns `200 OK`
     * idempotently.
     *
     * Closing dispatches an `EvaluationClosed` event, which recalculates the
     * offering's aggregate scores and concordance (via OfferingConsensusService).
     *
     * **Authorization:** requires the `specialist` role and permission to update
     * the evaluation (enforced by the evaluation policy). A specialist who does
     * not own the evaluation receives `403 Forbidden`.
     *
     * @group Evaluations
     *
     * @authenticated
     *
     * @urlParam evaluation_ulid string required The ULID of the evaluation. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @response 200 scenario="Closed successfully" {"message": "Evaluation closed."}
     * @response 200 scenario="Already closed" {"message": "Evaluation closed."}
     * @response 403 scenario="Not the owner" {"message": "One or more evaluations do not belong to the user."}
     * @response 409 scenario="Evaluation incomplete" {"message": "Evaluation is incomplete, all scores need to be provided, and at least one cata attribute for each MainTastes, mouthfeel and the set of sensorial axis."}
     */
    public function close(Request $request, Evaluation $evaluation)
    {
        if ($evaluation->status === 'closed') {
            return response()->json(['message' => __('evaluations.closed')], 200);
        }

        $this->service->setMainAttributes($request);

        if (! $this->service->isReadyForClosing()) {
            abort(409, __('evaluations.incomplete_evaluation'));
        }

        $evaluation->status = 'closed';
        $evaluation->save();
        event(new \App\Events\EvaluationClosed($evaluation->offering_id));

        return response()->json(['message' => __('evaluations.closed')], 200);
    }

    /**
     * Delete an evaluation
     *
     * Permanently deletes an evaluation. This action cannot be undone.
     *
     * **Authorization:** requires the `specialist` role and permission to delete
     * the evaluation (enforced by the evaluation policy). A specialist who does
     * not own the evaluation receives `403 Forbidden`.
     *
     * @group Evaluations
     *
     * @authenticated
     * @urlParam evaluation_ulid string required The ULID of the evaluation. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @response 200 scenario="Deleted" {"message": "Evaluation(s) deleted."}
     * @response 403 scenario="Not the owner" {"message": "One or more evaluations do not belong to the user."}
     */
    public function destroy(Evaluation $evaluation)
    {
        $evaluation->delete();

        return response()->json(['message' => __('evaluations.deleted')], 200);
    }
}
