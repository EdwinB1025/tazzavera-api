<?php

namespace App\Http\Controllers;

use App\Contracts\EvaluationServiceContract;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\UpdateEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{

    public function __construct(private EvaluationServiceContract $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
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
     * Closing and existing evaluation
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
