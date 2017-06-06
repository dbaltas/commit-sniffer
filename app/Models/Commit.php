<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commit extends Model
{
	protected $table = 'Commit';

	protected $fillable = ['sha1', 'committer', 'author', 'subject', 'mergeCommit', 'selfMerged', 'tracked'];
}
