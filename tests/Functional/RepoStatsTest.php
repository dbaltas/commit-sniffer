<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RepoStatsTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @group functional
     */
    public function testRepoStatsOnSameRepo()
    {
        Artisan::call('repo:stats', [
            'repo' => '.'
        ]);

        $expectedOutput = <<<OUTPUT
+----------------------------+-----+--------+-------------+---------+-----------+------+
| team                       | ALL | Direct | Self Merges | Tracked | Tracked % | PR % |
+----------------------------+-----+--------+-------------+---------+-----------+------+
| dbaltas@travelplanet24.com | 1   | 1      | 0           | 0       | 0.00      | 0.00 |
| TOTAL                      | 1   | 1      | 0           | 0       | 0.00      | 0.00 |
+----------------------------+-----+--------+-------------+---------+-----------+------+

OUTPUT;

        $actualOutput = Artisan::output();
        $this->assertEquals($expectedOutput, $actualOutput);
    }
}
