<?php

namespace App\Plugins;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitLogPlugin extends Plugin
{
    private $_path;

    /**
     * @param array $options
     * @return GitLogPlugin
     * @throws \Exception
     */
    public function __construct($options)
    {
        if (!isset($options['path'])) {
            throw new \Exception('Repository path not provided.');
        }
        $this->_path = $options['path'];
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return array
     */
    public function getCommitHistory(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $dateRange = $this->formatDateRange($dateFrom, $dateTo);

        $command = sprintf('git -C %s log --no-merges %s --format="%%H %%aE %%cE %%s"', $this->_path, $dateRange);

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

        $command = sprintf('git -C %s log --merges %s --format="%%H %%aE"', $this->_path, $dateRange);

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
        $command = sprintf('git -C %s log %s^..%s --format="%%H"', $this->_path, $mergeSha1, $mergeSha1);

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
