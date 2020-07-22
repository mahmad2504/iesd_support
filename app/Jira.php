<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use App\JiraFields;
use App\Ticket;
class Jira
{
	public function __construct()
	{
		$this->jf = new JiraFields();
	}
	public function Sync($jql,$updated=null)
	{
		$max = 500;
		$start = 0;
		$issueService = new IssueService();
		if($updated!=null)
			$jql = $jql." and updated >= '".$updated."'";

		echo "Query for active tickets \n".$jql."\n";
		$expand = [];//['changelog'];
		$fields = [];
		foreach($this->jf->Standard() as $field)
			$fields[] = $field;
		foreach($this->jf->Custom() as $field)
			$fields[] = $field;

		$issues = [];
		$start = 0;
		$max = 500;
		//dump($fields);
		while(1)
		{
			$data = $issueService->search($jql,$start, $max,$fields,$expand);
			if(count($data->issues) < $max)
			{
				foreach($data->issues as $issue)
				{
					$ticket = new Ticket($issue);
					$issues[] = $ticket ;
				}
				//echo count($issues)." Found"."\n";
				return $issues;
			}
			foreach($data->issues as $issue)
			{
				$ticket = new Ticket($issue);
				$issues[] = $ticket ;	
			}
			$start = $start + count($data->issues);
		}
		
	}
}