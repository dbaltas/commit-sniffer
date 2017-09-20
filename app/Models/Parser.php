<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Parser
{
    protected $_header;
    protected $_data;
    protected $_metrics;

    public function parse()
    {
        $data = [];

        DB::insert("insert into AuthorMap(author,map) select distinct c.author, c.author from `Commit` c left join AuthorMap m on m.author=c.author where m.author is null");

        $mapResults = DB::select('select distinct map from AuthorMap');
        foreach ($mapResults as $key => $mapResult) {
            $data[$mapResult->map] = [
                'team' => $mapResult->map
            ];
        }

        $data['TOTAL'] = [
            'team' => 'TOTAL',
            'ALL' => 0
        ];
        $this->_header = ['team', 'ALL'];

        $allCommits = DB::select('select m.map, count(c.id) as cnt from AuthorMap m left join `Commit` c on c.author = m.author group by m.map;');
        foreach ($allCommits as $key => $row) {
            $data[$row->map]['ALL'] = $row->cnt;
            $data['TOTAL']['ALL'] += $row->cnt;
        }

        if (!$this->_metrics || in_array('direct', $this->_metrics)) {
            $this->_header[] = 'Direct';
            $data['TOTAL']['Direct'] = 0;

            $directCommits = DB::select('select m.map, count(c.id) as cnt from AuthorMap m left join `Commit` c on c.author = m.author and c.mergeCommit is null group by m.map;');
            foreach ($directCommits as $key => $row) {
                $data[$row->map]['Direct'] = $row->cnt;
                $data['TOTAL']['Direct'] += $row->cnt;
            }
        }

        if (!$this->_metrics || in_array('self-merges', $this->_metrics)) {
            $this->_header[] = 'Self Merges';
            $data['TOTAL']['Self Merges'] = 0;

            $selfMergesCommits = DB::select('select m.map, count(c.id) as cnt from AuthorMap m left join `Commit` c on c.author = m.author and c.selfMerged = 1 group by m.map;');
            foreach ($selfMergesCommits as $key => $row) {
                $data[$row->map]['Self Merges'] = $row->cnt;
                $data['TOTAL']['Self Merges'] += $row->cnt;
            }
        }

        if (!$this->_metrics || in_array('tracked', $this->_metrics)) {
            $this->_header[] = 'Tracked';
            $this->_header[] = 'Tracked %';
            $data['TOTAL']['Tracked'] = 0;
            $data['TOTAL']['Tracked %'] = 0;

            $trackedCommits = DB::select('select m.map, count(c.id) as cnt from AuthorMap m left join `Commit` c on c.author = m.author and c.tracked = 1 group by m.map;');
            foreach ($trackedCommits as $key => $row) {
                $data[$row->map]['Tracked'] = $row->cnt;
                $data['TOTAL']['Tracked'] += $row->cnt;
            }

            foreach ($data as $key => $value) {
                $data[$key]['Tracked %'] = ($value['ALL'] > 0) ? number_format(100 * ($value['Tracked'] / $value['ALL']), 2) : 'N/A';
            }
        }

        if (!$this->_metrics || in_array('pull-requests', $this->_metrics)) {
            $this->_header[] = 'PRs';
            $this->_header[] = 'PRs %';
            $data['TOTAL']['PRs'] = 0;
            $data['TOTAL']['PRs %'] = 0;

            $merges = DB::select('select m.map, count(c.id) as cnt from AuthorMap m left join `Commit` c on c.author = m.author and not c.mergeCommit is null group by m.map;');
            foreach ($merges as $key => $row) {
                $data[$row->map]['PRs'] = $row->cnt;
                $data['TOTAL']['PRs'] += $row->cnt;
            }

            foreach ($data as $key => $value) {
                $data[$key]['PRs %'] = ($value['ALL'] > 0) ? number_format(100 * ($value['PRs'] / $value['ALL']), 2) : 'N/A';
            }
        }

        if (!$this->_metrics || in_array('reverts', $this->_metrics)) {
            $this->_header[] = 'Reverts';
            $data['TOTAL']['Reverts'] = 0;

            $revertCommits = DB::select('select m.map, count(c.id) as cnt from AuthorMap m left join `Commit` c on c.author = m.author and c.revert = 1 group by m.map;');
            foreach ($revertCommits as $key => $row) {
                $data[$row->map]['Reverts'] = $row->cnt;
                $data['TOTAL']['Reverts'] += $row->cnt;
            }
        }

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

    public function setMetrics($metrics)
    {
        $this->_metrics = $metrics ? explode(',', $metrics) : [];
        return $this;
    }
}
