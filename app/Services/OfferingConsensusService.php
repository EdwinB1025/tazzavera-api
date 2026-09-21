<?php

namespace App\Services;

use App\Models\AxisConcordance;
use App\Models\Offering;
use App\Models\OlfactoryTaxonomy;

class OfferingConsensusService
{
    private const MIN_EVALUATIONS = 5;
    private const SIGMA_MAX_AFFECTIVE = 4;
    private const SIGMA_MAX_DESCRIPTIVE = 7.5;

    private const AFFECTIVE_AXES = ['fragrance', 'aroma', 'flavor', 'aftertaste', 'acidity', 'sweetness', 'mouthfeel', 'overall'];
    private const DESCRIPTIVE_AXES = ['fragrance', 'aroma', 'flavor', 'aftertaste', 'acidity', 'sweetness', 'mouthfeel'];

    public function recompute(int $offeringId): void
    {
        $offering = Offering::find($offeringId);

        if (! $offering) {
            return;
        }

        $evaluations = $offering->evaluations()
            ->where('status', 'closed')
            ->where('evaluation_type', 'specialist')
            ->get();

        if ($evaluations->count() < self::MIN_EVALUATIONS) {
            return;
        }

        $averages = $this->computeAffectiveAverages($evaluations);

        $affective = $this->computeConcordance($evaluations, 'affective', self::AFFECTIVE_AXES, self::SIGMA_MAX_AFFECTIVE);
        $descriptive = $this->computeConcordance($evaluations, 'descriptive', self::DESCRIPTIVE_AXES, self::SIGMA_MAX_DESCRIPTIVE);

        $cuppingAvg = $this->computeCuppingAvg($evaluations, $affective['global']);

        foreach ($averages as $column => $value) {
            $offering->{$column} = $value;
        }
        $offering->cupping_avg = $cuppingAvg;
        $offering->concordance_affective = $affective['global'];
        $offering->concordance_descriptive = $descriptive['global'];
        $offering->evaluation_count = $evaluations->count();
        $offering->defective_evaluation_count = $evaluations->where('is_defective', true)->count();
        $offering->verification_status = 'verified';
        $offering->save();

        $this->storeAxisConcordances($offering, 'affective', $affective['perAxis']);
        $this->storeAxisConcordances($offering, 'descriptive', $descriptive['perAxis']);
        $this->populateOfferingTastes($offering, $evaluations);
    }

    private function computeAffectiveAverages($evaluations): array
    {
        $averages = [];
        foreach (self::AFFECTIVE_AXES as $axis) {
            $averages["{$axis}_avg"] = round(
                $evaluations->avg(fn($e) => $e->affective[$axis]['score']),
                1
            );
        }
        return $averages;
    }

    private function stdDev(array $scores): float
    {
        $n = count($scores);
        $mean = array_sum($scores) / $n;
        $variance = array_sum(array_map(fn($x) => ($x - $mean) ** 2, $scores)) / $n;
        return sqrt($variance);
    }

    private function computeConcordance($evaluations, string $part, array $axes, float $sigmaMax): array
    {
        $perAxis = [];
        foreach ($axes as $axis) {
            $scores = $evaluations->map(fn($e) => $e->{$part}[$axis]['score'])->values()->all();
            dump($part, $axis, $scores);
            $sigma = $this->stdDev($scores);
            $perAxis[$axis] = round(max(0, 1 - $sigma / $sigmaMax), 3);
        }
        $global = round(array_sum($perAxis) / count($perAxis), 3);

        return ['global' => $global, 'perAxis' => $perAxis];
    }

    private function computeCuppingAvg($evaluations, float $concordanceAffectiveGlobal): float
    {
        $sum = 0;
        foreach (self::AFFECTIVE_AXES as $axis) {
            $sum += $evaluations->avg(fn($e) => $e->affective[$axis]['score']);
        }

        $u = (1 - $concordanceAffectiveGlobal) * 5;
        $d = $evaluations->avg(fn($e) => $e->is_defective ? 1 : 0) * 5;

        $score = 0.65625 * $sum + 52.75 - 2 * $u - 4 * $d;

        return round($score * 4) / 4;
    }

    private function storeAxisConcordances(Offering $offering, string $cvaType, array $perAxis): void
    {
        foreach ($perAxis as $axis => $value) {
            AxisConcordance::updateOrCreate(
                ['offering_id' => $offering->id, 'cva_type' => $cvaType, 'axis' => $axis],
                ['value' => $value]
            );
        }
    }

    private function populateOfferingTastes(Offering $offering, $evaluations): void
    {
        $marks = [];
        foreach ($evaluations as $evaluation) {
            foreach ($evaluation->tastes as $taste) {
                $key = $taste->taxonomy_ref . '|' . $taste->type;
                $marks[$key]['taxonomy_ref'] = $taste->taxonomy_ref;
                $marks[$key]['type'] = $taste->type;
                $marks[$key]['evals'][$evaluation->id] = true;
            }
        }

        $refIds = collect($marks)->pluck('taxonomy_ref')->unique();
        $nodes = OlfactoryTaxonomy::with('parent.parent')
            ->whereIn('id', $refIds)
            ->get()
            ->keyBy('id');

        $aggregated = [];
        foreach ($marks as $mark) {
            $type = $mark['type'];
            $node = $nodes[$mark['taxonomy_ref']] ?? null;

            while ($node !== null) {
                $key = $node->id . '|' . $type;
                if (! isset($aggregated[$key])) {
                    $aggregated[$key] = [
                        'taxonomy_ref' => $node->id,
                        'type'         => $type,
                        'level'        => (string) $node->level,
                        'evals'        => [],
                    ];
                }
                $aggregated[$key]['evals'] += $mark['evals'];
                $node = $node->parent;
            }
        }

        $offering->offeringTastes()->delete();

        $byLevel = collect($aggregated)->groupBy('level');
        $createdIds = [];

        foreach (['0', '1', '2'] as $level) {
            foreach ($byLevel->get($level, []) as $row) {
                $parentId = $this->resolveParentOfferingTasteId(
                    $nodes,
                    $row['taxonomy_ref'],
                    $row['type'],
                    $createdIds
                );

                $created = $offering->offeringTastes()->create([
                    'taxonomy_ref' => $row['taxonomy_ref'],
                    'type'         => $row['type'],
                    'level'        => $row['level'],
                    'parent_id'    => $parentId,
                    'count'        => count($row['evals']),
                ]);

                $createdIds[$row['taxonomy_ref'] . '|' . $row['type']] = $created->id;
            }
        }
    }

    private function resolveParentOfferingTasteId($nodes, $taxonomyRef, string $type, array $createdIds): ?int
    {
        $node = $nodes[$taxonomyRef] ?? null;
        $parent = $node?->parent;
        if ($parent === null) {
            return null;
        }
        return $createdIds[$parent->id . '|' . $type] ?? null;
    }
}
