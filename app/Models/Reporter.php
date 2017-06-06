<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Reporter
{
	function report()
	{
		$report = [];

		$mapNewAuthorsToNone = DB::insert("insert into AuthorMap(author,map) select distinct c.author, 'NONE' from Commit c left join AuthorMap m on m.author=c.author where m.author is null");

		$mapResults = DB::select('select distinct map from AuthorMap');
		$maps = [];
		foreach ($mapResults as $key => $mapResult) {
			$maps[] = $mapResult->map;
			$report[$mapResult->map] = [
				'ALL' => 0,
				'Direct' => 0,
				'Self Merges' => 0,
				'Tracked' => 0,
			];	
		}

		$report['TOTAL'] = [
			'ALL' => 0,
			'Direct' => 0,
			'Self Merges' => 0,
			'Tracked' => 0,
		];

		$allCommits = DB::select('select m.map, count(*) as cnt from Commit c left join AuthorMap m on c.author = m.author group by m.map;');
		$directCommits = DB::select('select m.map, count(*) as cnt from Commit c left join AuthorMap m on c.author = m.author where c.mergeCommit is null group by m.map;');
		$trackedCommits = DB::select('select m.map, count(*) as cnt from Commit c left join AuthorMap m on c.author = m.author where c.tracked=1 group by m.map;');
		$selfMergesCommits = DB::select('select m.map, count(*) as cnt from Commit c left join AuthorMap m on c.author = m.author where c.selfMerged=1 group by m.map;');

		foreach ($allCommits as $key => $row)
		{
			$report[$row->map]['ALL'] = $row->cnt;
			$report['TOTAL']['ALL'] += $row->cnt;
		}

		foreach ($directCommits as $key => $row)
		{
			$report[$row->map]['Direct'] = $row->cnt;
			$report['TOTAL']['Direct'] += $row->cnt;
		}

		foreach ($trackedCommits as $key => $row)
		{
			$report[$row->map]['Tracked'] = $row->cnt;
			$report['TOTAL']['Tracked'] += $row->cnt;
		}

		foreach ($selfMergesCommits as $key => $row)
		{
			$report[$row->map]['Self Merges'] = $row->cnt;
			$report['TOTAL']['Self Merges'] += $row->cnt;
		}

		echo "\tALL\tDirect\tTracked\tSelf Merges\tTracked %\tPR %\n";
		foreach ($report as $key => $value) {
			echo sprintf("%s\t%s\t%s\t%s\t%s\t%.2F\t%.2F\n", $key, $value['ALL'], $value['Direct'], $value['Tracked'],
				$value['Self Merges'],
				100*$value['Tracked']/$value['ALL'],
				100*($value['ALL']-$value['Direct']-$value['Self Merges'])/$value['ALL']);
		}
	}
}