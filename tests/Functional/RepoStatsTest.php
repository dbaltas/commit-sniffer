<?php

use Tests\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RepoStatsTest extends TestCase
{
    /**
     * @group functional
     */
    public function testRepoStatsOnSameRepo()
    {
        $this->createDatabase();

        $process = new Process($this->getMigrateCommand());
        $process->run();

        if (!$process->isSuccessful()) {
            $this->fail("Migrate failed" . $process->getOutput());
        }

        $process = new Process($this->getCommand());
        $process->run();

        if (!$process->isSuccessful()) {
            $this->fail("command failed" . $process->getOutput());
        }

        $expectedOutput = <<<OUTPUT
+----------------------------+-----+--------+-------------+---------+-----------+------+
| team                       | ALL | Direct | Self Merges | Tracked | Tracked % | PR % |
+----------------------------+-----+--------+-------------+---------+-----------+------+
| dbaltas@travelplanet24.com | 1   | 1      | 0           | 0       | 0.00      | 0.00 |
| TOTAL                      | 1   | 1      | 0           | 0       | 0.00      | 0.00 |
+----------------------------+-----+--------+-------------+---------+-----------+------+

OUTPUT;
        $this->assertEquals($expectedOutput, $process->getOutput());
    }

    /**
     * @param string|null $memoryLimit, if null
     */
    protected function getCommand($memoryLimit = null)
    {
        $cmd = sprintf("./artisan repo:stats . --date-from 'May 1 2017'  --date-to 'JUN 1 2017'");

        if ($memoryLimit) {
            $cmd = "php -d memory_limit=$memoryLimit " . $cmd;
        }

        $cmd = 'APP_ENV=functional-test ' . $cmd;

        return $cmd;
    }

    protected function getMigrateCommand($memoryLimit = null)
    {
        $cmd = sprintf("./artisan migrate");

        if ($memoryLimit) {
            $cmd = "php -d memory_limit=$memoryLimit " . $cmd;
        }

        $cmd = 'APP_ENV=functional-test ' . $cmd;

        return $cmd;
    }

    protected function createDatabase()
    {
        $process = new Process('rm -f database/functional.sqlite && touch database/functional.sqlite');

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
