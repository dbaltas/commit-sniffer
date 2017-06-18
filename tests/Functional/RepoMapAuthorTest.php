<?php

use Tests\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RepoMapAuthorTest extends TestCase
{
    /**
     * @group functional
     */
    public function testMapAuthorCreateAuthor()
    {
        $this->createDatabase();

        $process = new Process($this->getMigrateCommand());
        $process->run();
        if (!$process->isSuccessful()) {
            $this->fail('Migrate failed');
        }

        $expectedAuthor = 'dbaltas@travelplanet24.com';
        $expectedMap = 'dbaltas@travelplanet24.com';

        $process = new Process($this->getCommand($expectedAuthor, $expectedMap));
        $process->run();
        if (!$process->isSuccessful()) {
            $this->fail('Command failed');
        }

        $author = DB::select('SELECT author, map FROM AuthorMap;');
        $this->assertEquals($expectedAuthor, $author[0]->author);
        $this->assertEquals($expectedMap, $author[0]->map);
    }

    /**
     * @param string $author
     * @param string $map
     * @param string|null $memoryLimit, if null
     * @return string
     */
    protected function getCommand($author, $map, $memoryLimit = null)
    {
        $cmd = sprintf('./artisan repo:map-author %s %s', $author, $map);

        if ($memoryLimit) {
            $cmd = 'php -d memory_limit=$memoryLimit ' . $cmd;
        }

        $cmd = 'APP_ENV=functional-test ' . $cmd;

        return $cmd;
    }

    protected function getMigrateCommand($memoryLimit = null)
    {
        $cmd = sprintf('./artisan migrate');

        if ($memoryLimit) {
            $cmd = 'php -d memory_limit=$memoryLimit ' . $cmd;
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
