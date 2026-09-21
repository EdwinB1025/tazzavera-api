<?php

namespace App\Contracts;

use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\UpdateEvaluationRequest;
use App\Models\Evaluation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

interface EvaluationServiceContract
{
    public function setMainAttributes(StoreEvaluationRequest|UpdateEvaluationRequest|Request $request): void;

    public function parseEvaluation(StoreEvaluationRequest|UpdateEvaluationRequest|Request $request): void;

    public function saveNewEvaluation(): void;

    public function updateEvaluation(): void;

    public function getEvaluation(): Evaluation;

    public function isReadyForClosing(): bool;
}
