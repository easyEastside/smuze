<?php

namespace App\Console\Commands;

use App\Models\ServerMetric;
use Illuminate\Console\Command;

class PruneMetrics extends Command
{
    protected $signature = 'metrics:prune {--days=14 : Remove metrics older than this many days}';

    protected $description = 'Prune old server metrics data';

    public function handle(): void
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $deleted = ServerMetric::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} old metric records.");
    }
}
