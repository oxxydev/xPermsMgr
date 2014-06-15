<?php

namespace _64FF00\xPermsMgr;

use pocketmine\command\CommandSender;

use pocketmine\IPlayer;

use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;

use pocketmine\Player;

use pocketmine\utils\Config;

class xPMUsers
{
	public function __construct(xPermsMgr $plugin)
	{
		$this->groups = new xPMGroups($plugin);
		
		$this->plugin = $plugin;
	}
	
	public function getAll()
	{
		return array_diff(scandir($this->plugin->getDataFolder() . "players/"), array(".", "..", ""));
	}
	
	public function getAttachment($player)
	{
		return $player->addAttachment($this->plugin);
	}
	
	public function getConfig($target)
	{
		if($target instanceof IPlayer)
		{
			if(!(file_exists($this->plugin->getDataFolder() . "players/" . strtolower($target->getName()) . ".yml")))
			{
				return new Config($this->plugin->getDataFolder() . "players/" . strtolower($target->getName()) . ".yml", Config::YAML, array(
					"username" => $target->getName(),
					"group" => $this->groups->getDefaultGroup(),
					"permissions" => array(
					)
				));
			}
			else
			{
				return new Config($this->plugin->getDataFolder() . "players/" . strtolower($target->getName()) . ".yml", Config::YAML, array(
				));
			}
		}
		
		return new Config($this->plugin->getDataFolder() . "players/" . $target, Config::YAML, array(
		));
	}
	
	public function getEffectivePermissions($player)
	{
		$permissions = array();

		foreach($player->getEffectivePermissions() as $permission)
		{			
			array_push($permissions, $permission->getPermission());
		}
		
		return $permissions;
	}
	
	public function getCurrentGroup($player)
	{		
		return $this->getConfig($player)->getAll()["group"];
	}
	
	public function getPermissions($player)
	{
		$inherited_groups = $this->groups->getGroup($this->getCurrentGroup($player))["inheritance"];
		
		$permissions = $this->groups->getGroup($this->getCurrentGroup($player))["permissions"];
		
		$user_permissions = $this->getUserPermissions($player);
		
		foreach($user_permissions as $u_permission)
		{
			$permissions = array_merge($permissions, $u_permission);
		}
		
		if(isset($inherited_groups) and is_array($inherited_groups))
		{
			foreach($inherited_groups as $i_group)
			{
				if($this->groups->isValidGroup($i_group) != null)
				{
					$permissions = array_merge($permissions, $this->groups->getGroup($i_group)["permissions"]);
				}
			}
		}
		
		return $permissions;
	}
	
	public function getUserPermissions($player)
	{
		return $this->getConfig($player)->getAll()["permissions"];
	}
	
	public function setGroup($player, $groupName)
	{
		if($this->groups->isValidGroup($groupName))
		{
			$user_cfg = $this->getConfig($player);
			
			$user_cfg->set("group", $groupName);
			
			$user_cfg->save();
			
			$this->setPermissions($player);
			
			unset($user_cfg);
			
			return true;
		}	
		
		return false;
	}
	
	public function setPermissions($player)
	{
		if($player instanceof Player)
		{			
			$attachment = $this->getAttachment($player);
		
			foreach(array_keys($attachment->getPermissions()) as $key)
			{
				$attachment->unsetPermission($key);
			}	

			foreach($this->getPermissions($player) as $permission)
			{
				$attachment->setPermission($permission, true);
			}

			$player->recalculatePermissions();
		}
	}
}
