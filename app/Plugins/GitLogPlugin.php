<?php

namespace App\Plugins;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitLogPlugin extends Plugin
{
    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return array
     */
    public function getCommitHistory(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $dateRange = $this->formatDateRange($dateFrom, $dateTo);

        $command = sprintf('git log --no-merges %s --format="%%H %%aE %%cE %%s"', $dateRange);

        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        return array_filter(explode(PHP_EOL, $output));
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return array
     */
    public function getMergeHistory(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $dateRange = $this->formatDateRange($dateFrom, $dateTo);

        $command = sprintf('git log --merges %s --format="%%H %%aE"', $dateRange);

        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        return array_filter(explode(PHP_EOL, $output));
    }

    /**
     * @param string $mergeSha1
     * @return array
     */
    public function getCommitsInMerge($mergeSha1)
    {
        $command = sprintf('git log %s^..%s --format="%%H"', $mergeSha1, $mergeSha1);

        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        return array_filter(explode(PHP_EOL, $output));
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return string
     */
    private function formatDateRange($dateFrom, $dateTo)
    {
        return sprintf('--since "%s" --until "%s"', $dateFrom->format('M d Y'), $dateTo->format('M d Y'));
    }
}
