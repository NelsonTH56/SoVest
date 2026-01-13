<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prediction;
use App\Jobs\EvaluatePredictionJob;
use Carbon\Carbon;

class EvaluateExpiredPredictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:evaluate
                            {--limit=50 : Maximum number of predictions to process}
                            {--force : Force evaluation even if already processed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate all expired predictions and update user scores';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting evaluation of expired predictions...');

        $limit = $this->option('limit');
        $force = $this->option('force');

        // Build query for expired predictions
        $query = Prediction::where('is_active', 1)
            ->where('end_date', '<=', Carbon::now()->format('Y-m-d'))
            ->with(['stock', 'user']);

        if (!$force) {
            $query->whereNull('accuracy');
        }

        $predictions = $query->limit($limit)->get();

        $count = $predictions->count();

        if ($count === 0) {
            $this->info('No expired predictions found to evaluate.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} expired prediction(s) to evaluate.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($predictions as $prediction) {
            try {
                // Dispatch job to queue
                EvaluatePredictionJob::dispatch($prediction->prediction_id);
                $successCount++;
            } catch (\Exception $e) {
                $this->error("\nError dispatching job for prediction {$prediction->prediction_id}: " . $e->getMessage());
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Dispatched {$successCount} prediction evaluation job(s) to queue.");

        if ($errorCount > 0) {
            $this->warn("{$errorCount} prediction(s) failed to dispatch.");
        }

        $this->info('Run "php artisan queue:work" to process the jobs.');

        return Command::SUCCESS;
    }
}
