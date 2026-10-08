<?php

namespace App\Listeners;

use App\Events\EvaluationClosed;
use App\Services\OfferingConsensusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RecalculateConsensus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(EvaluationClosed $event): void
    {
        (new OfferingConsensusService())->recompute($event->offeringId);
    }
}
