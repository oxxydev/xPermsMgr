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
	private $playerAttachments = array();
	
	public function __construct(xPermsMgr $plugin)
	{
		@mkdir($plugin->getDataFolder() . "players/", 0777, true);
		
		$this->config = new xPMConfiguration($plugin);
		$this->groups = new xPMGroups($plugin);
		
		$this->plugin = $plugin;
	}
	
	public function getAll()
	{
		return array_diff(scandir($this->plugin->getDataFolder() . "players/"), array(".", "..", ""));
	}
	
	public function getAttachment($player)
	{
		if(!isset($this->playerAttachments[$player->getName()]))
		{
			$this->playerAttachments[$player->getName()] = $player->addAttachment($this->plugin);
		}
		
		return $this->playerAttachments[$player->getName()];
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
						"test.permission.t1",
						"-test.permission.t2"
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
	
	public function getGroup($player)
	{		
		return $this->getConfig($player)->getAll()["group"];
	}
	
	public function getNameTag($player)
	{
		$prefix = $this->groups->getPrefix($this->getGroup($player));
		
		$suffix = $this->groups->getSuffix($this->getGroup($player));
		
		if($this->config->getConfig()["custom-nametag"] != null)
		{
			return str_replace("{PREFIX}", $prefix, str_replace(
				"{USER_NAME}", $player->getName(), str_replace(
					"{SUFFIX}", $suffix, $this->config->getConfig()["custom-nametag"]
					)
				)
			);
		}
	}
	
	public function getPermissions($player)
	{
		$inherited_groups = $this->groups->getGroup($this->getGroup($player))["inheritance"];
		
		$permissions = array_merge($this->groups->getGroup($this->getGroup($player))["permissions"], $this->getUserPermissions($player));
		
		if(isset($inherited_groups) and is_array($inherited_groups))
		{
			foreach($inherited_groups as $inherited_group)
			{
				if($this->groups->isValidGroup($inherited_group) != null)
				{
					$permissions = array_merge($permissions, $this->groups->getGroup($inherited_group)["permissions"]);
				}
			}
		}
		
		return $permissions;
	}
	
	public function getUserPermissions($player)
	{
		return $this->getConfig($player)->get("permissions");
	}
	
	public function isNegative($permission)
	{
		return substr($permission, 1) === "-";
	}
	
	public function setGroup($player, $groupName)
	{
		if($this->groups->isValidGroup($groupName))
		{
			$user_cfg = $this->getConfig($player);
			
			$user_cfg->set("group", $groupName);
			
			$user_cfg->save();
			
			$this->setPermissions($player);
			
			$this->setNameTag($player);
			
			unset($user_cfg);
			
			return true;
		}	
		
		return false;
	}
	
	public function setNameTag($player)
	{
		$player->setNameTag($this->getNameTag($player));
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
				if(!$this->isNegative($permission))
				{
					$attachment->setPermission($permission, true);
				}
				else
				{
					$attachment->setPermission($permission, false);
				}
			}

			$player->recalculatePermissions();
		}
	}
}