<?php

namespace _64FF00\xPermsMgr;

use pocketmine\utils\Config;

class xPMGroups
{
	private $groups;
	
	public function __construct(xPermsMgr $plugin)
	{
		$this->plugin = $plugin;
		
		$this->load();
	}
	
	public function getAllGroups()
	{
		$groups = array_keys($this->groups);
		
		foreach($groups as $group)
		{
			if(!($this->isValidGroupName($group)))
			{
				unset($group);
			}
		}
		
		return $groups;
	}
	
	public function getDefaultGroup()
	{
		foreach($this->getAllGroups() as $group)
		{
			if(isset($this->groups[$group]["default-group"]) and $this->groups[$group]["default-group"])
			{
				return $group;
			}
		}
		
		return null;
	}
	
	public function getGroup($groupName)
	{
		if($this->isValidGroup($groupName))
		{
			return $this->groups[$groupName];
		}
		
		return null;
	}
	
	public function getPrefix($groupName)
	{
		return $this->groups[$groupName]["prefix"];
	}
	
	public function getSuffix($groupName)
	{
		return $this->groups[$groupName]["suffix"];
	}
	
	public function isValidGroup($groupName)
	{
		return isset($groupName) ? isset($this->groups[$groupName]) : null;
	}
	
	public function isValidGroupName($groupName)
	{
		return preg_match("/^[0-9a-zA-Z\xA1-\xFE]$/", $groupName);
	}
	
	public function load()
	{
		if(!(file_exists($this->plugin->getDataFolder() . "groups.yml")))
		{
			$this->plugin->saveResource("groups.yml");
		}
		
		$this->groups = (new Config($this->plugin->getDataFolder() . "groups.yml", Config::YAML, array(
		)))->getAll();
	}
}