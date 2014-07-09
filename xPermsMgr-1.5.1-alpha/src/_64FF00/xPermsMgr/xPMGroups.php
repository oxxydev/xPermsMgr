<?php

namespace _64FF00\xPermsMgr;

use pocketmine\level\Level;

use pocketmine\utils\Config;

class xPMGroups
{
	private $groups;
	
	public function __construct(xPermsMgr $plugin)
	{
		$this->plugin = $plugin;
		
		$this->load();
	}
	
	public function getAlias($groupName)
	{
		return $this->groups->getAll()[$groupName]["alias"];
	}
	
	public function getAllGroups()
	{
		$groups = array_keys($this->groups->getAll());
		
		foreach($groups as $group)
		{
			if(!($this->isValidGroupName($group)))
			{
				unset($group);
			}
		}
		
		return $groups;
	}
	
	public function getByAlias($alias)
	{
		foreach($this->getAllGroups() as $group)
		{
			if($this->getAlias($group) == $alias)
			{
				return $group;
			}
		}
		
		return null;
	}
	
	public function getDefaultGroup()
	{
		foreach($this->getAllGroups() as $group)
		{
			if(isset($this->groups->getAll()[$group]["default-group"]) and $this->groups->getAll()[$group]["default-group"])
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
			return $this->groups->getAll()[$groupName];
		}
		
		return null;
	}

	public function getGroupPermissions($groupName, $level)
	{
		$inherited_groups = $this->getGroup($groupName)["inheritance"];
		
		$permissions = $this->loadWorldsData($groupName)["worlds"][$level->getName()]["permissions"];
		
		if(isset($inherited_groups) and is_array($inherited_groups))
		{
			foreach($inherited_groups as $inherited_group)
			{
				if($this->isValidGroup($inherited_group) != null)
				{
					$permissions = array_merge($permissions, $this->loadWorldsData($inherited_group)["worlds"][$level->getName()]["permissions"]);
				}
			}
		}
		
		return $permissions;
	}
	
	public function getGroupPrefix($groupName)
	{
		return $this->groups->getAll()[$groupName]["prefix"];
	}
	
	public function getGroupSuffix($groupName)
	{
		return $this->groups->getAll()[$groupName]["suffix"];
	}
	
	public function isValidGroup($groupName)
	{
		return isset($groupName) ? isset($this->groups->getAll()[$groupName]) : null;
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
		
		$this->groups = new Config($this->plugin->getDataFolder() . "groups.yml", Config::YAML, array(
		));
	}
	
	private function loadWorldsData($groupName)
	{
		foreach($this->plugin->getServer()->getLevels() as $level)
		{
			$temp_groups = $this->groups->getAll();
			
			if(!isset($temp_groups[$groupName]["worlds"][$level->getName()]))
			{
				$temp_groups[$groupName]["worlds"][$level->getName()] = array(
					"permissions" => array(
					),
				);
			}
		}
		
		$this->groups->setAll($temp_groups);
		
		$this->groups->save();
		
		return $this->getGroup($groupName);
	}
}