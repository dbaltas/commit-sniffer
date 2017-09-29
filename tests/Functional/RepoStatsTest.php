<?php

use Tests\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RepoStatsTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        $this->createDatabase();

        $process = new Process($this->getMigrateCommand());
        $process->run();

        if (!$process->isSuccessful()) {
            $this->fail('Migrate failed' . $process->getOutput());
        }
    }

    /**
     * @group functional
     */
    public function testRepoStatsOnSameRepo()
    {
        $process = new Process($this->getCommand());
        $process->run();

        if (!$process->isSuccessful()) {
            $this->fail("command failed" . $process->getOutput());
        }

        $expectedOutput = <<<OUTPUT
+----------------------------+-----+--------+-------------+---------+-----------+-----+-------+---------+
| team                       | ALL | Direct | Self Merges | Tracked | Tracked % | PRs | PRs % | Reverts |
+----------------------------+-----+--------+-------------+---------+-----------+-----+-------+---------+
| dbaltas@travelplanet24.com | 1   | 1      | 0           | 0       | 0.00      | 0   | 0.00  | 0       |
| TOTAL                      | 1   | 1      | 0           | 0       | 0.00      | 0   | 0.00  | 0       |
+----------------------------+-----+--------+-------------+---------+-----------+-----+-------+---------+

OUTPUT;
        $this->assertEquals($expectedOutput, $process->getOutput());
    }

    /**
     * @group functional
     */
    public function testRepoStatsWithSpecificMetricsOnSameRepo()
    {
        $process = new Process($this->getCommandWithMetricsArgument());
        $process->run();

        if (!$process->isSuccessful()) {
            $this->fail("command failed" . $process->getOutput());
        }

        $expectedOutput = <<<OUTPUT
+----------------------------+-----+--------+---------+-----------+
| team                       | ALL | Direct | Tracked | Tracked % |
+----------------------------+-----+--------+---------+-----------+
| dbaltas@travelplanet24.com | 1   | 1      | 0       | 0.00      |
| TOTAL                      | 1   | 1      | 0       | 0.00      |
+----------------------------+-----+--------+---------+-----------+

OUTPUT;
        $this->assertEquals($expectedOutput, $process->getOutput());
    }

    /**
     * @group functional
     */
    public function testRepoStatsWithInvalidDatesReturnsError()
    {
        $process = new Process($this->getCommand(null, 'fooDateFrom', 'barDateTo'));
        $process->run();

        $this->assertFalse($process->isSuccessful());
        $expectedOutput = <<<OUTPUT
Invalid date given.

OUTPUT;
        $this->assertEquals($expectedOutput, $process->getOutput());
    }

    /**
     * @param string|null $memoryLimit
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return string
     */
    protected function getCommand($memoryLimit = null, $dateFrom = null, $dateTo = null)
    {
        $dateFrom = $dateFrom ?: 'May 1 2017';
        $dateTo = $dateTo ?: 'JUN 1 2017';
        $cmd = sprintf("./artisan repo:stats . --date-from='$dateFrom' --date-to='$dateTo'");

        if ($memoryLimit) {
            $cmd = "php -d memory_limit=$memoryLimit " . $cmd;
        }

        $cmd = 'APP_ENV=functional-test ' . $cmd;

        return $cmd;
    }

    /**
     * @param string|null $memoryLimit
     * @return string
     */
    protected function getCommandWithMetricsArgument($memoryLimit = null)
    {
        $cmd = sprintf("./artisan repo:stats --date-from 'May 1 2017' --date-to 'JUN 1 2017' --metrics=direct,tracked .");

        if ($memoryLimit) {
            $cmd = "php -d memory_limit=$memoryLimit " . $cmd;
        }

        $cmd = 'APP_ENV=functional-test ' . $cmd;

        return $cmd;
    }

    /**
     * @param null $memoryLimit
     * @return string
     */
    protected function getMigrateCommand($memoryLimit = null)
    {
        $cmd = sprintf("./artisan migrate");

        if ($memoryLimit) {
            $cmd = "php -d memory_limit=$memoryLimit " . $cmd;
        }

        $cmd = 'APP_ENV=functional-test ' . $cmd;

        return $cmd;
    }

    /**
     * @return void
     * @throws ProcessFailedException
     */
    protected function createDatabase()
    {
        $process = new Process('rm -f database/functional.sqlite && touch database/functional.sqlite');

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
