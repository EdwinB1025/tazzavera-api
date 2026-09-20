<?php

namespace App\Services;

use App\Models\AxisConcordance;
use App\Models\Offering;

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
        $offering->save();

        $this->storeAxisConcordances($offering, 'affective', $affective['perAxis']);
        $this->storeAxisConcordances($offering, 'descriptive', $descriptive['perAxis']);
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
}
