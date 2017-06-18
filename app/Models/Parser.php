<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Parser
{
    protected $_header;
    protected $_data;

    public function parse()
    {
        $data = [];

        $mapNewAuthorsToSelf = DB::insert("insert into AuthorMap(author,map) select distinct c.author, c.author from `Commit` c left join AuthorMap m on m.author=c.author where m.author is null");

        $mapResults = DB::select('select distinct map from AuthorMap');
        $maps = [];
        foreach ($mapResults as $key => $mapResult) {
            $maps[] = $mapResult->map;
            $data[$mapResult->map] = [
                'team' => $mapResult->map,
                'ALL' => 0,
                'Direct' => 0,
                'Self Merges' => 0,
                'Tracked' => 0,
                'Tracked %' => 0,
                'PR %' => 0
            ];
        }

        $data['TOTAL'] = [
            'team' => 'TOTAL',
            'ALL' => 0,
            'Direct' => 0,
            'Self Merges' => 0,
            'Tracked' => 0,
            'Tracked %' => 0,
            'PR %' => 0
        ];

        $allCommits = DB::select('select m.map, count(*) as cnt from `Commit` c left join AuthorMap m on c.author = m.author group by m.map;');
        $directCommits = DB::select('select m.map, count(*) as cnt from `Commit` c left join AuthorMap m on c.author = m.author where c.mergeCommit is null group by m.map;');
        $trackedCommits = DB::select('select m.map, count(*) as cnt from `Commit` c left join AuthorMap m on c.author = m.author where c.tracked=1 group by m.map;');
        $selfMergesCommits = DB::select('select m.map, count(*) as cnt from `Commit` c left join AuthorMap m on c.author = m.author where c.selfMerged=1 group by m.map;');

        foreach ($allCommits as $key => $row) {
            $data[$row->map]['ALL'] = $row->cnt;
            $data['TOTAL']['ALL'] += $row->cnt;
        }

        foreach ($directCommits as $key => $row) {
            $data[$row->map]['Direct'] = $row->cnt;
            $data['TOTAL']['Direct'] += $row->cnt;
        }

        foreach ($trackedCommits as $key => $row) {
            $data[$row->map]['Tracked'] = $row->cnt;
            $data['TOTAL']['Tracked'] += $row->cnt;
        }

        foreach ($selfMergesCommits as $key => $row) {
            $data[$row->map]['Self Merges'] = $row->cnt;
            $data['TOTAL']['Self Merges'] += $row->cnt;
        }

        foreach ($data as $key => $value) {
            $data[$key]['Tracked %'] = ($value['ALL'] > 0) ? number_format(100*$value['Tracked']/$value['ALL'], 2) : 'N/A';
            $data[$key]['PR %'] = ($value['ALL'] > 0) ? number_format(100*($value['ALL']-$value['Direct']-$value['Self Merges'])/$value['ALL'], 2) : 'N/A';
        }

        $this->_header = ['team','ALL','Direct','Self Merges','Tracked','Tracked %','PR %'];
        $this->_data = $data;
        return;
    }

    public function getHeader()
    {
        return $this->_header;
    }

    public function getData()
    {
        return $this->_data;
    }
}
