<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('app:purge-expired-certifications')]
#[Description('Deletes exprired certificatios to the date.')]
class PurgeExpiredCertifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = DB::table('certifications')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("{$deleted} certificaciones vencidas eliminadas.");
        Log::info('Certificaciones vencidas purgadas', [
            'command' => $this->signature,
            'deleted' => $deleted,
        ]);
    }
}
