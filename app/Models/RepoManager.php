<?php

namespace App\Models;

class RepoManager
{
    public function takeMeTo($repo) : bool
    {
        if (!is_dir($repo)) {
            throw new \Exception("$repo not a valid directory");
        }
        chdir($repo);
        return true;
    }
}
