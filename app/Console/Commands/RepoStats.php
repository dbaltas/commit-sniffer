<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RepoManager;
use App\Models\GitLogRunner;
use App\Models\Parser;
use App\Models\Commit;

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
     * @return void
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
        $repoManager = new RepoManager;
        $gitLogRunner = new GitLogRunner;

        $repo = $this->argument('repo');
        $repoManager->takeMeTo($repo);

        $dateFrom = $this->option("date-from");
        $dateTo = $this->option("date-to");
        ($dateFrom && $dateTo)
            ? $gitLogRunner->setDateRangeCustom($dateFrom, $dateTo)
            : $gitLogRunner->setDateRangeLastMonth();

        $commits = $gitLogRunner->run();

        Commit::truncate();
        foreach ($commits as $key => $commitAttributes) {
            $commit = new Commit($commitAttributes);
            $commit->save();
        }

        $parser = new Parser;
        $parser->parse();
        $this->table($parser->getHeader(), $parser->getData());
    }
}
