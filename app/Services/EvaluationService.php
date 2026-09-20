<?php

namespace App\Services;

use App\Contracts\EvaluationServiceContract;
use App\Enums\RoastLevel;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\UpdateEvaluationRequest;
use App\Models\Evaluation;
use App\Models\Offering;
use App\Models\OlfactoryTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class EvaluationService implements EvaluationServiceContract
{

    public readonly Offering $offering;
    public readonly Request $request;
    public Evaluation $evaluation;
    public Collection $tastes;
    public Collection $affective;
    public Collection $descriptive;
    public Collection $extrinsics;
    /**
     * Create a new class instance.
     */

    private function setMainAttributes(StoreEvaluationRequest|UpdateEvaluationRequest|Request $request): void
    {
        if (! isset($this->request)) {
            $this->request = $request;
        }

        if (! $request->isMethod('post') && ! isset($this->evaluation)) {
            $this->evaluation = $request->route('evaluation');
            $this->affective = collect($this->evaluation->affective);
            $this->descriptive = collect($this->evaluation->descriptive);
            $this->extrinsics = collect($this->evaluation->extrinsics);
            $this->tastes = $this->evaluation->tastes->toBase();
            $offering = $this->evaluation->offering;
        } else {
            $offering = Offering::where('ulid', $request->validated('offeringId'))->firstOrFail();
        }

        if (! isset($this->offering)) {
            $this->offering = $offering;
        }
    }

    private function setTastes(): void
    {
        $this->tastes = collect();
        //** Parse descriptive */
        $descriptive = $this->request->validated('descriptive');
        $cataAttributes = [];

        // Retrieving cataAttributes from descriptive
        foreach ($descriptive as $axis => $data) {
            if (in_array($axis, ['mainTastes', 'fragrance', 'aroma', 'flavor', 'aftertaste', 'mouthfeel'], true)) {
                $refs = $axis === 'mainTastes' ? $data : ($data['cata'] ?? []);
                foreach ($refs as $ref) {
                    $cataAttributes[] = [
                        'taxonomy_ref' => $ref,
                        'type' => $axis === 'mainTastes' ? 'main_tastes' : $axis
                    ];
                }
            }
        }

        //Retrieving defects from affective

        $refs = $this->request->validated('affective.defects') ?? [];
        foreach ($refs as $ref) {
            $cataAttributes[] = ['taxonomy_ref' => $ref, 'type' => 'defects'];
        }
        if (!empty($cataAttributes)) {
            //Homologating ids
            $idByUlid = OlfactoryTaxonomy::whereIn('ulid', array_column($cataAttributes, 'taxonomy_ref'))
                ->pluck('id', 'ulid');

            //Setting array to create the relationship
            $this->tastes = collect($cataAttributes)->map(
                fn($cata) => [
                    'taxonomy_ref' => $idByUlid[$cata['taxonomy_ref']],
                    'type'         => $cata['type'],

                ]
            );
        }
    }

    private function parseJsonColumns(): void
    {

        $jsonColumns = $this->request
            ->safe()
            ->only(['descriptive', 'affective', 'extrinsics']);

        $this->affective = collect();
        $this->descriptive = collect();

        foreach ($jsonColumns as $column => $data) {

            switch ($column) {
                case 'extrinsics':
                    $this->extrinsics = collect($data);
                    break;
                case 'affective':
                case 'descriptive':
                    foreach ($data as $axis => $array) {
                        if (! in_array($axis, ['roastLevel', 'mainTastes', 'defects'], true)) {
                            $arrayFiltered = array_intersect_key($array, array_flip(['score', 'note']));
                            if ($column === 'affective') {
                                $this->affective->put($axis, $arrayFiltered);
                            } else {
                                $this->descriptive->put($axis, $arrayFiltered);
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    }

    private function computeCuppingScore(): float
    {
        $totalPoints = $this->affective->sum(fn($axis) => $axis['score']);
        $score = 0.65625 * $totalPoints + 52.75;

        return round($score, 2);
    }

    public function parseEvaluation(StoreEvaluationRequest|UpdateEvaluationRequest|Request $request): void
    {

        $this->setMainAttributes($request);

        $this->setTastes();

        $this->parseJsonColumns();

        $dataFill = [
            'extraction_method' => $request->validated('extractionMethod'),
            'descriptive' => $this->descriptive->all(),
            'affective' => $this->affective->all(),
            'extrinsics' => $this->extrinsics->all(),
        ];

        if (isset($this->evaluation)) {
            $evaluation = $this->evaluation->fill($dataFill);
        } else {
            $evaluation = new Evaluation($dataFill);
            $evaluation->status = 'open';
        }

        $this->evaluation = $evaluation;

        if ($this->affective->where('score', '>', 0)->count() === 8) {
            $this->evaluation->cupping_score = $this->computeCuppingScore();
        }

        $this->evaluation->is_defective = count($this->request->validated('affective.defects') ?? []) > 0;
    }

    public function saveEvaluation(): void
    {
        DB::transaction(function () {
            $evaluation = $this->evaluation;
            $evaluation->evaluator()->associate($this->request->user());
            $evaluation->offering()->associate($this->offering);
            $evaluation->save();
            $evaluation->refresh();


            $evaluation->tastes()->createMany($this->tastes->all());
            $evaluation->load('tastes.taxonomy:id,ulid');


            $this->evaluation = $evaluation;
        });
    }


    public function updateEvaluation(): void
    {
        DB::transaction(function () {
            $evaluation = $this->evaluation;
            $evaluation->save();
            $evaluation->refresh();

            /**EDB 09/18/26 load the updated values posted by the client, the db taste values are refreshed inside a transaction so the delete and recreate are atomic */
            $evaluation->tastes()->delete();
            $evaluation->tastes()->createMany($this->tastes->all());
            $evaluation->load('tastes.taxonomy:id,ulid');


            $this->evaluation = $evaluation;
        });
    }

    public function getEvaluation(): Evaluation
    {
        return $this->evaluation;
    }
}
