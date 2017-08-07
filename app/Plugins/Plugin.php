<?php

namespace App\Plugins;

abstract class Plugin
{
    abstract public function getCommitHistory(\DateTime $dateFrom, \DateTime $dateTo);

    abstract public function getMergeHistory(\DateTime $dateFrom, \DateTime $dateTo);

    abstract public function getCommitsInMerge($mergeSha1);
}
