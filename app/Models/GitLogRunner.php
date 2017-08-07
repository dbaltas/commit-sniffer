<?php

namespace App\Models;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitLogRunner
{
    protected $_dateRange;
    protected $_mergeAuthor = [];
    protected $_commitsInMerges = [];
    protected $_commits = [];

    protected $_mergeAuthorsPath;
    protected $_commitMergePath;

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return string
     */
    private function formatDateRange($dateFrom, $dateTo)
    {
        return sprintf('--since "%s" --until "%s"', $dateFrom->format("M d Y"), $dateTo->format("M d Y"));
    }

    /**
     * @return void
     */
    public function setDefaultDateRange()
    {
        $this->setDateRange('first day of previous month', 'last day of previous month');
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @return void
     */
    public function setDateRange($dateFrom, $dateTo)
    {
        $dateFrom = new \DateTime($dateFrom);
        $dateTo = new \DateTime($dateTo);
        $this->_dateRange = $this->formatDateRange($dateFrom, $dateTo);
    }

    public function run()
    {
        if (!$this->_dateRange) {
            throw new \Exception('You need to define a date range');
        }

        $this->_getMerges();
        $this->_getCommitsInMerges();
        $this->_getCommits();

        return $this->_commits;
    }

    protected function _getMerges()
    {
        $lines = $this->_getMergeHistory();

        foreach ($lines as $line) {
            list($sha1, $author) = explode(' ', $line);
            $this->_mergeAuthor[$sha1] = trim(preg_replace('/\s\s+/', ' ', $author));
        }
    }

    protected function _getCommits()
    {
        $lines = $this->_getCommitHistory();

        foreach ($lines as $line) {
            list($sha1, $author, $committer, $subject) = explode(' ', $line, 4);
            $subject = trim(preg_replace('/\s\s+/', ' ', $subject));

            $this->_commits[$sha1] = [
                'sha1' => $sha1,
                'author' => $author,
                'committer' => $committer,
                'subject' => $subject,
                'mergeCommit' => null,
                'selfMerged' => false,
                'tracked' => false,
                'revert' => false
            ];

            if (isset($this->_commitsInMerges[$sha1])) {
                $mergeSha1 = $this->_commitsInMerges[$sha1];
                $this->_commits[$sha1]['mergeCommit'] = $mergeSha1;
                if ($author == $committer && $committer == $this->_mergeAuthor[$mergeSha1]) {
                    $this->_commits[$sha1]['selfMerged'] = true;
                }
            } else {
                if (preg_match('/\(#[0-9]+\)$/', $subject)) {
                    $this->_commits[$sha1]['mergeCommit'] = $sha1;
                    if ($author == $committer || $committer == 'noreply@github.com') {
                        $this->_commits[$sha1]['selfMerged'] = true;
                    }
                }
            }

            if (preg_match('/\[#[A-Z]-[0-9]+\]/', $subject)) {
                $this->_commits[$sha1]['tracked'] = true;
            }

            if (preg_match('/Revert\s\".*\"/', $subject)) {
                $this->_commits[$sha1]['revert'] = true;
            }
        }
    }

    protected function _getCommitsInMerges()
    {
        foreach ($this->_mergeAuthor as $mergeSha1 => $value) {
            $lines = $this->_getCommitsInMergeHistory($mergeSha1);

            foreach ($lines as $line) {
                $sha1 = trim(preg_replace('/\s\s+/', ' ', $line));
                $this->_commitsInMerges[$sha1] = $mergeSha1;
            }
        }
    }

    protected function _getMergeHistory()
    {
        $command = sprintf('git log --merges %s --format="%%H %%aE"', $this->_dateRange);

        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        return array_filter(explode(PHP_EOL, $output));
    }

    protected function _getCommitHistory()
    {
        $command = sprintf('git log --no-merges %s --format="%%H %%aE %%cE %%s"', $this->_dateRange);

        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        return array_filter(explode(PHP_EOL, $output));
    }

    protected function _getCommitsInMergeHistory($mergeSha1)
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
}
