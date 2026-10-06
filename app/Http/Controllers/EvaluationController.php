<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterEvaluationRequest;
use App\Http\Requests\FilterUserEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use App\Models\User;

/**EDB 10/06/26: Public reads only. The specialist's own reads and every write live in UserEvaluationController */
class EvaluationController extends Controller
{

    /**
     * List evaluations
     *
     * Returns a paginated list of sensory evaluations, each including its taste
     * notes (olfactory taxonomy references) and the offering it belongs to.
     * Supports filtering and sorting via query parameters.
     *
     * @group Evaluations
     *
     * @unauthenticated
     * @responseFile storage/scribe/responses/evaluations.index.json
     */
    public function index(FilterEvaluationRequest $request)
    {
        $evaluations = Evaluation::query()
            ->filter($request->validated())
            ->with('tastes.taxonomy:id,ulid', 'offering:id,ulid')
            ->paginate();

        return EvaluationResource::collection($evaluations);
    }

    /**
     * List the evaluations of a user
     *
     * Returns a paginated list of the evaluations made by the given user
     * (specialist), each including its taste notes and the offering it belongs
     * to. Supports the same filtering and sorting as the public list, except the
     * evaluator, which is the user in the path. An unknown user ULID returns
     * `404 Not Found`.
     *
     * @group Evaluations
     *
     * @unauthenticated
     *
     * @urlParam user_ulid string required The ULID of the user (evaluator). Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @responseFile storage/scribe/responses/evaluations.index.json
     */
    public function indexByUser(FilterUserEvaluationRequest $request, User $user)
    {
        $evaluations = $user->evaluations()
            ->filter($request->validated())
            ->with('tastes.taxonomy:id,ulid', 'offering:id,ulid')
            ->paginate();

        return EvaluationResource::collection($evaluations);
    }

    /**
     * Get an evaluation
     *
     * Returns a single evaluation with its taste notes and associated offering.
     *
     * @group Evaluations
     *
     * @unauthenticated
     *
     * @urlParam evaluation_ulid string required The ULID of the evaluation. Example: 01M35F5RX4ADGC3CSDXXYB08DA
     *
     * @responseFile storage/scribe/responses/evaluations.show.json
     */
    public function show(Evaluation $evaluation)
    {
        $evaluation->load('tastes.taxonomy:id,ulid', 'offering:id,ulid');

        return new EvaluationResource($evaluation);
    }
}
