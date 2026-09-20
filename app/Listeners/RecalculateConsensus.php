<?php

namespace App\Listeners;

use App\Events\EvaluationClosed;
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
        Log::info("Consensus recalculation triggered for offering {$event->offeringId}");
    }
}
