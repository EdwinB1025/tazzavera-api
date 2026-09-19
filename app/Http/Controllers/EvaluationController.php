<?php

namespace App\Http\Controllers;

use App\Contracts\EvaluationServiceContract;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Resources\EvaluationResource;
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
        $this->service->saveEvaluation();

        return EvaluationResource::collection($this->service->getEvaluation())
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
