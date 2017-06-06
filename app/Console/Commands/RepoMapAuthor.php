<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuthorMap;

class RepoMapAuthor extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'repo:map-author {author} {map}';


	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$author = $this->argument('author');
		$map = $this->argument('map');

		$authorMap = AuthorMap::where('author', $author)->firstOrFail();
		$authorMap->map = $map;
		$authorMap->save();
	}
}
