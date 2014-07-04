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
					"worlds" => array(
						$this->plugin->getServer()->getDefaultLevel()->getName() => array(
							"group" => $this->groups->getDefaultGroup(),
							"permissions" => array(
							),
						)
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
	
	public function getGroup($player, $level)
	{		
		return $this->getConfig($player)->getAll()["worlds"][$level]["group"];
	}
	
	public function getNameTag($player, $level)
	{
		$group = $this->getGroup($player, $level);

		if($this->config->getConfig()["custom-nametag"] != null)
		{
			return str_replace("{PREFIX}", $this->groups->getPrefix($group), str_replace(
				"{USER_NAME}", $player->getName(), str_replace(
					"{SUFFIX}", $this->groups->getSuffix($group), $this->config->getConfig()["custom-nametag"]
					)
				)
			);
		}
	}
	
	public function getPermissions($player, $level)
	{
		$inherited_groups = $this->groups->getGroup($this->getGroup($player, $level))["inheritance"];
		
		$permissions = array_merge($this->groups->getGroup($this->getGroup($player, $level))["permissions"], $this->getUserPermissions($player, $level));
		
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
	
	public function getUserPermissions($player, $level)
	{
		return $this->getConfig($player)->getAll()["worlds"][$level]["permissions"];
	}
	
	public function isNegative($permission)
	{
		return substr($permission, 1) === "-";
	}
	
	public function setGroup($player, $level, $groupName)
	{
		if($this->groups->isValidGroup($groupName))
		{
			$user_cfg = $this->getConfig($player)->getAll();
			
			$user_cfg["worlds"][$level]["group"] = $groupName;
			
			$this->getConfig($player)->setAll($user_cfg);
			
			$this->setPermissions($player, $level);
			
			$this->setNameTag($player, $level);
			
			unset($user_cfg);
			
			return true;
		}	
		
		return false;
	}
	
	public function setNameTag($player, $level)
	{
		if($player instanceof Player)
		{
			$player->setNameTag($this->getNameTag($player, $level));
		}
	}
	
	public function setPermissions($player, $level)
	{
		if($player instanceof Player)
		{			
			$attachment = $this->getAttachment($player);
		
			foreach(array_keys($attachment->getPermissions()) as $key)
			{
				$attachment->unsetPermission($key);
			}	

			foreach($this->getPermissions($player, $level) as $permission)
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