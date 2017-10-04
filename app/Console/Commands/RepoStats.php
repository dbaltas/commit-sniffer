<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\ParserService;
use App\Services\ReporterService;
use App\Models\Commit;
use App\Plugins\GitLogPlugin;

class RepoStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        repo:stats
        {repo : Repository path}
        {--date-from= : Date since}
        {--date-to= : Date until}
        {--metrics= : Comma separated metric names to be displayed}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print stats for the repo';

    /**
     * Create a new command instance.
     *
     * @return RepoStats
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $repo = $this->argument('repo');

        $plugin = new GitLogPlugin([
            'path' => $repo
        ]);
        $parser = new ParserService($plugin);

        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        try {
            ($dateFrom && $dateTo)
                ? $parser->setDateRange($dateFrom, $dateTo)
                : $parser->setDefaultDateRange();
        } catch (\Exception $ex) {
            $this->line('Invalid date given.');
            exit(1);
        }
        $commits = $parser->parse();

        Commit::truncate();
        foreach ($commits as $key => $commitAttributes) {
            $commit = new Commit($commitAttributes);
            $commit->save();
        }

        $reporter = new ReporterService();
        $metrics = $this->option('metrics');
        $reporter->setMetrics($metrics)
            ->prepare();
        $this->table($reporter->getHeader(), $reporter->getData());
    }
}
