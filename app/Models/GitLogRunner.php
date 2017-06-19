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
    private function formatDateRange($dateFrom, $dateTo) : string
    {
        return sprintf('--since "%s" --until "%s"', $dateFrom->format("M d Y"), $dateTo->format("M d Y"));
    }

    /**
     * @return void
     */
    function setDateRangeLastMonth() : void
    {
        $this->setDateRange("first day of previous month", "last day of previous month");
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @return void
     */
    function setDateRange($dateFrom, $dateTo) : void
    {
        $dateFrom = new \DateTime($dateFrom);
        $dateTo = new \DateTime($dateTo);
        $this->_dateRange = $this->formatDateRange($dateFrom, $dateTo);
    }

    function run()
    {
        if (!$this->_dateRange) {
            throw new \Exception('You need to define a date range');
        }

        $this->_initTempFiles();
        $this->_getMerges();
        $this->_getCommitsInMerges();
        $this->_getCommits();

        return $this->_commits;
    }

    protected function _getMerges()
    {
        $process = new Process($this->_getMergesCommand());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $handle = fopen($this->_mergeAuthorsPath, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                list($sha1, $author) = explode(' ', $line);
                $this->_mergeAuthor[$sha1] = trim(preg_replace('/\s\s+/', ' ', $author));
            }
            fclose($handle);
        }
    }

    protected function _getCommits()
    {
        $process = new Process($this->_getCommitsCommand());
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $handle = fopen($this->_commitsPath, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                list($sha1, $author, $committer, $subject) = explode(' ', $line, 4);
                $subject = trim(preg_replace('/\s\s+/', ' ', $subject));

                $this->_commits[$sha1] = [
                    'sha1' => $sha1,
                    'author' => $author,
                    'committer' => $committer,
                    'subject' => $subject,
                    'mergeCommit' => null,
                    'selfMerged' => false,
                    'tracked' => false
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
            }
            fclose($handle);
        }
    }

    protected function _getCommitsInMerges()
    {
        foreach ($this->_mergeAuthor as $mergeSha1 => $value) {
            $process = new Process($this->_getCommitsInMergeCommand($mergeSha1));
            $process->run();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $handle = fopen($this->_commitMergePath, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $sha1 = trim(preg_replace('/\s\s+/', ' ', $line));
                    $this->_commitsInMerges[$sha1] = $mergeSha1;
                }
                fclose($handle);
            }
        }
    }

    protected function _initTempFiles()
    {
        $this->_mergeAuthorsPath = tempnam(sys_get_temp_dir(), 'merge_authors.txt');
        $this->_commitMergePath = tempnam(sys_get_temp_dir(), 'commit_merge.txt');
        $this->_commitsPath = tempnam(sys_get_temp_dir(), 'commits.txt');
    }

    protected function _getMergesCommand()
    {
        return sprintf('git log --merges %s --format="%%H %%aE" > %s', $this->_dateRange, $this->_mergeAuthorsPath);
    }

    protected function _getCommitsCommand()
    {
        return sprintf('git log --no-merges %s --format="%%H %%aE %%cE %%s" > %s', $this->_dateRange, $this->_commitsPath);
    }

    protected function _getCommitsInMergeCommand($mergeSha1)
    {
        return sprintf('git log %s^..%s --format="%%H" > %s', $mergeSha1, $mergeSha1, $this->_commitMergePath);
    }
}
