<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\JiraFields;
use Carbon\Carbon;
class Ticket
{
    function __construct($issue) 
	{
		$jf = new JiraFields();
		foreach($jf->Standard() as $field)
		{
			$this->$field = $this->GetValue($field,$issue);
			if($this->$field == null)
				$this->$field = '';
			else
			{
				if($this->$field instanceof \DateTime) 
				{
					$this->SetTimeZone($this->$field);
					$carbon = Carbon::instance($this->$field);
					$carbon->second = 0;
					$this->$field = $carbon->getTimestamp();//$this->$field->format('Y-m-d H:i');
				}
				else if($this->$field==-1)
				{
					
				}
				else if(is_array($this->$field))
				{
				}
				else if(strtotime($this->$field)!=false)
				{
					
					$dt = new \DateTime($this->$field);
					$this->SetTimeZone($dt);
					$carbon = Carbon::instance($dt);
					$carbon->second = 0;
					$this->$field = $carbon->getTimestamp();//$dt->format('Y-m-d H:i');
				}
				else if(is_object($this->$field))
				{
					dd($field." is object . Correct it ".__FILE__." line=".__LINE__);
				}
			}
		}
		foreach($jf->Custom() as $field=>$code)
		{
			
			$this->$field = $this->GetValue($code,$issue);
			if(is_object($this->$field))
			{
				dump($this->$field);
				dd($field." is object . Correct it ".__FILE__." line=".__LINE__);
			}
				
			if($this->$field == null)
				$this->$field = '';
			
			else if(strtotime($this->$field)!=false)
			{
				$dt = new \DateTime($this->$field);
				$this->SetTimeZone($dt);
				$carbon = Carbon::instance($dt);
				$carbon->second = 0;
				$this->$field = $carbon->getTimestamp();//$dt->format('Y-m-d H:i');
			}
		}
	}
	static function SetTimeZone($datetime)
	{
		$datetime->setTimezone(new \DateTimeZone("Asia/Karachi"));
	}
	
	function DateToState($state)
	{
		$retval = null;
		for($i=0;$i<count($this->transitions);$i++)
		{
			$transition = $this->transitions[$i];
			if(strcasecmp($transition->toString,$state))
			{
				$retval = $transition->created;
			}
		}
		return $retval;
	}
	public function Process()
	{
	
		
	}
	private function GetValue($prop,$issue,$alternative=null)
	{
		switch($prop)
		{
			case 'components':
				$cstr = [];
				if(isset($issue->fields->components))
				{
					
					foreach($issue->fields->components as $component)
					{
						$cstr[] = $component->name;
					}
				}
				if(count($cstr)==0)
					$cstr[] = 'none';
				//dump($cstr);
				return $cstr;
				break;
			case 'project':
				if(isset($issue->fields->project->key))
					return $issue->fields->project->key;
				else 
					return '';
				break;
			case 'resolutiondate':
				if(isset($issue->fields->resolutiondate))
				{
					return  $issue->fields->resolutiondate;
				}
				else 
				{
					return -1;
				}
				break;
			case 'resolution':
			    if(isset($issue->fields->resolution->name))
				{
					return  $issue->fields->resolution->name;
				}
				else 
					return '';
				break;
			case 'status':
				return  $issue->fields->status->name;
				break;
			case 'issuetype':
				return  $issue->fields->issuetype->name;
				break;
			case 'statuscategory':
				if(!isset($issue->fields->status))
					dd("ERROR::Enable status fields for statuscategory");
				if($issue->fields->status->statuscategory->id == 2)
					return 'OPEN';
				else if($issue->fields->status->statuscategory->id == 3)
					return 'RESOLVED';
				else if($issue->fields->status->statuscategory->id == 4)
					return 'INPROGRESS';
				else
				{
					echo $issue->key." has unknown category";
					exit();
				}
				break;
			case 'priority':
				if(isset($issue->fields->priority))
				{
					if($issue->fields->priority->name == 'Blocker')
						return 1;
					else if($issue->fields->priority->name == 'Critical')
						return 2;
					else if($issue->fields->priority->name == 'Major')
						return 3;
					else
						return 4;
				}
				break;
			case 'transitions':
				$transitions = [];
				foreach($issue->changelog->histories as $history)
				{
					foreach($history->items as $item)
					{
						if($item->field == "status")
						{
							$item->created= new \DateTime($history->created);
							self::SetTimeZone($item->created);
							$carbon = Carbon::instance($item->created);
							$carbon->second = 0;
							$nitem=new \StdClass();
							$nitem->created = $carbon->getTimeStamp();
							$nitem->stateto = $item->toString;  
							$nitem->statefrom = $item->fromString;
							$transitions[] = $nitem;
						}
					}
				}
				return $transitions;
				break;
				
			//// Custom Fields //////////////////////////////////////
			case 'customfield_13106'://'reason_for_closure':
				if(isset($issue->fields->customFields[$prop]))
				{
					return $issue->fields->customFields[$prop]->value;
				}
			default:
				if(isset($issue->$prop))
					return $issue->$prop;
				else if(isset($issue->fields->$prop))
					return $issue->fields->$prop;
				else if(isset($issue->fields->customFields[$prop]))
				{
					return $issue->fields->customFields[$prop];
				}
				else
					return null;
		}
	}
}
