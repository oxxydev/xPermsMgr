<?php

namespace _64FF00\xPermsMgr;

use pocketmine\command\CommandSender;

use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;

use pocketmine\Player;

use pocketmine\Server;

use pocketmine\utils\Config;

class xPMUsers
{
	public function __construct(xPermsMgr $plugin)
	{
		$this->groups = new xPMGroups($plugin);
		
		$this->plugin = $plugin;
	}
	
	public function getConfig($player)
	{
		$username = $player->getName();
		
		if(!(file_exists($this->plugin->getDataFolder() . "players/" . strtolower($username) . ".yml")))
		{
			return new Config($this->plugin->getDataFolder() . "players/" . strtolower($username) . ".yml", Config::YAML, array(
				"username" => $username,
				"group" => $this->groups->getDefaultGroup(),
			));
		}
		else
		{
			return new Config($this->plugin->getDataFolder() . "players/" . strtolower($username) . ".yml", Config::YAML, array(
			));
		}
	}
	
	public function getAll()
	{
		return array_diff(scandir($this->plugin->getDataFolder() . "players/"), array(".", "..", ""));
	}
	
	public function getAttachment($player)
	{
		return $player->addAttachment($this->plugin);
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
	
	public function getGroup($player)
	{		
		return $this->getConfig($player)->getAll()["group"];
	}
	
	public function getPerms($player)
	{
		$inherited_groups = $this->groups->getGroup($this->getGroup($player))["inheritance"];
		
		$permissions = $this->groups->getGroup($this->getGroup($player))["permissions"];
		
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
	
	public function recalculatePerms()
	{
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player)
		{
			$player->recalculatePermissions();
		}
	}
	
	public function removeAttachment($player)
	{
		$player->removeAttachment($this->getAttachment($player));
	}
	
	public function setGroup($player, $groupName)
	{
		if($this->groups->isValidGroup($groupName))
		{
			$user_cfg = $this->getConfig($player);
			
			$user_cfg->set("group", $groupName);
			
			$user_cfg->save();
			
			$this->setPerms($player);
			
			unset($user_cfg);
			
			return true;
		}	
		
		return false;
	}
	
	public function setPerms($player)
	{
		if($player instanceof Player)
		{	
			$this->unsetPerms($player);
			
			foreach($this->getPerms($player) as $permission)
			{				
				$this->getAttachment($player)->setPermission($permission, true);
			}			
		}
	}

	public function unsetPerms($player)
	{
		if($player instanceof Player)
		{
			foreach($this->getEffectivePermissions($player) as $permission)
			{				
				$this->getAttachment($player)->unsetPermission($permission);
			}
		}
	}
}