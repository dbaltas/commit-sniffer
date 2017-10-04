<?php

namespace App\Services;

use App\Plugins\Plugin;

class ParserService
{
    protected $_dateFrom;
    protected $_dateTo;
    protected $_mergeAuthor = [];
    protected $_commitsInMerges = [];
    protected $_commits = [];

    protected $_mergeAuthorsPath;
    protected $_commitMergePath;

    /**
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {
        $this->_plugin = $plugin;
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
        $this->_dateFrom = new \DateTime($dateFrom);
        $this->_dateTo = new \DateTime($dateTo);
    }

    public function parse()
    {
        if (!$this->_dateFrom || !$this->_dateTo) {
            throw new \Exception('You need to define a date range');
        }

        $this->_getMerges();
        $this->_getMergeCommits();
        $this->_getCommits();

        return $this->_commits;
    }

    protected function _getMerges()
    {
        $lines = $this->_plugin->getMergeHistory($this->_dateFrom, $this->_dateTo);

        foreach ($lines as $line) {
            list($sha1, $author) = explode(' ', $line);
            $this->_mergeAuthor[$sha1] = trim(preg_replace('/\s\s+/', ' ', $author));
        }
    }

    protected function _getCommits()
    {
        $lines = $this->_plugin->getCommitHistory($this->_dateFrom, $this->_dateTo);

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

    protected function _getMergeCommits()
    {
        foreach ($this->_mergeAuthor as $mergeSha1 => $value) {
            $lines = $this->_plugin->getCommitsInMerge($mergeSha1);

            foreach ($lines as $line) {
                $sha1 = trim(preg_replace('/\s\s+/', ' ', $line));
                $this->_commitsInMerges[$sha1] = $mergeSha1;
            }
        }
    }
}
