<?php

namespace _64FF00\xPermsMgr;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerRespawnEvent as PlayerSpawnEvent;

use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\Server;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class xPermsMgr extends PluginBase implements CommandExecutor, Listener
{
	public function onEnable()
	{
		@mkdir($this->getDataFolder() . "players/", 0777, true);
		
		$this->loadConfigFile();
		$this->loadAllGroups();
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		console(TextFormat::GREEN . "[INFO] xPermsMgr has been enabled.");
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args)
	{
		switch($cmd->getName())
		{
			case "xpmgr":
				
				return $this->xPermsMgrCommand($sender, $cmd, $label, $args);
				
			default:
				
				return false;
		}
	}
	
	public function onPlayerChat(PlayerChatEvent $event)
	{
		if($this->config["chat-format"] != null)
		{
			$format = str_replace("{PREFIX}", $this->getPrefix($this->getPlayerRank($event->getPlayer())), str_replace(
				"{USER_NAME}", $event->getPlayer()->getName(), str_replace(
					"{SUFFIX}", $this->getSuffix($this->getPlayerRank($event->getPlayer())), str_replace(
						"{MESSAGE}", $event->getMessage(), $this->config["chat-format"]
						)
					)
				)
			);
		}
		else
		{
			$this->config["chat-format"] = "<" . $event->getPlayer()->getName() . "> " . $event->getMessage();
		}
		
		$event->setFormat($format);
	}
	
	public function onPlayerSpawn(PlayerSpawnEvent $event)
	{
		$this->setPlayerPermissions($event->getPlayer());
	}
	
	private function getAllGroups()
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
	
	private function getAllPlayerPermissions($player)
	{
		$inherited_group = $this->groups[$this->getPlayerRank($player)]["inheritance"];
		
		$perms = $this->groups[$this->getPlayerRank($player)]["permissions"];
		
		if(isset($inherited_group) and is_array($inherited_group))
		{
			foreach($inherited_group as $ig)
			{
				if($this->isValidGroup($ig))
				{
					$perms = array_merge($perms, $this->groups[$ig]["permissions"]);
				}
			}
		}
		
		return $perms;
	}
	
	private function getAllUserConfigFiles()
	{
		return array_diff(scandir($this->getDataFolder() . "players/"), array(".", "..", ""));
	}
	
	private function getDefaultGroup()
	{
		foreach($this->getAllGroups() as $group)
		{
			if(isset($this->groups[$group]["default-group"]) and $this->groups[$group]["default-group"])
			{
				return $group;
			}
		}
		
		return false;
	}
	
	private function getPlayerRank($player)
	{
		$cfg = $this->getUserConfig($player);
		
		return $cfg->getAll()["group"];
	}
	
	private function getPrefix($groupName)
	{
		return $this->groups[$groupName]["prefix"];
	}
	
	private function getSuffix($groupName)
	{
		return $this->groups[$groupName]["suffix"];
	}
	
	private function getUserConfig($player)
	{
		$username = $player->getName();
		
		if(!(file_exists($this->getDataFolder() . "players/" . strtolower($username) . ".yml")))
		{
			return new Config($this->getDataFolder() . "players/" . strtolower($username) . ".yml", Config::YAML, array(
				"username" => $username,
				"group" => $this->getDefaultGroup(),
			));
		}
		else
		{
			return new Config($this->getDataFolder() . "players/" . strtolower($username) . ".yml", Config::YAML, array(
			));
		}
	}
	
	private function getValidPlayer($username)
	{
		$player = $this->getServer()->getPlayer($username);
		
		return $player instanceof Player ? $player : $this->getServer()->getOfflinePlayer($username);
	}
	
	private function isValidGroup($groupName)
	{
		return isset($groupName) ? isset($this->groups[$groupName]) : false;
	}
	
	private function isValidGroupName($groupName)
	{
		return preg_match("/^[0-9a-zA-Z\xA1-\xFE]$/", $groupName);
	}
	
	private function loadAllGroups()
	{
		if(!(file_exists($this->getDataFolder() . "groups.yml")))
		{
			$this->saveResource("groups.yml");
		}
		
		$this->groups = (new Config($this->getDataFolder() . "groups.yml", Config::YAML, array(
		)))->getAll();
	}
	
	private function loadConfigFile()
	{
		if(!(file_exists($this->getDataFolder() . "config.yml")))
		{
			$this->saveDefaultConfig();
		}
		
		$this->config = $this->getConfig()->getAll();
	}
	
	private function setPlayerPermissions($player)
	{
		if($player instanceof Player)
		{
			foreach($this->getAllPlayerPermissions($player) as $permission)
			{
				$attachment = $player->addAttachment($this);
				
				$attachment->setPermission($permission, true);
			}
			
			$player->removeAttachment($attachment);
			
			unset($attachment);
			
			return true;
		}
		 
		return false;
	}
	
	private function setPlayerRank($player, $groupName)
	{		
		if($this->isValidGroup($groupName))
		{
			$user_cfg = $this->getUserConfig($player);
			
			$user_cfg->set("group", $groupName);
			
			$user_cfg->save();
			
			unset($user_cfg);
			
			return true;
		}	
		
		return false;
	}
	
	private function xPermsMgrCommand(CommandSender $sender, Command $cmd, $label, array $args)
	{
		if(!isset($args[0]))
		{
			$sender->sendMessage("[xPermsMgr] xPermsMgr v" . $this->getDescription()->getVersion() . " by " . $this->getDescription()->getAuthors()[0] . "!");
		}
		else
		{
			$output = "";

			switch($args[0])
			{				
				case "groups":
					
					foreach($this->getAllGroups() as $group)
					{
						$output .= $group . ", ";
					}

					$output = substr($output, 0, -2);

					$sender->sendMessage("[xPermsMgr] List of all groups: " . $output);
						
					break;
					
				case "reload":
				
					@mkdir($this->getDataFolder() . "players/", 0777, true);
				
					$this->loadConfigFile();
					$this->loadAllGroups();
					
					foreach($this->getServer()->getOnlinePlayers() as $player)
					{
						$player->recalculatePermissions();
					}
							
					$sender->sendMessage("[xPermsMgr] Successfully reloaded the config files and player permissions.");
							
					break;
						
				case "setrank":
					
					if(count($args) > 4)
					{
						$sender->sendMessage("[xPermsMgr] Usage: /xpmgr setrank <USER_NAME> <GROUP_NAME>");
							
						break;
					}
					
					if(!isset($args[1]))
					{
						$sender->sendMessage("[xPermsMgr] ERROR: Invalid Player!");
						
						break;
					}
					
					$target = $this->getValidPlayer($args[1]);
						
					if(isset($args[2]) and $this->isValidGroup($args[2]))
					{					
						$this->setPlayerRank($target, $args[2]);
						
						$this->setPlayerPermissions($target);
							
						$message = str_replace("{RANK}", strtolower($args[2]), $this->config["message-on-rank-change"]);
								
						$sender->sendMessage("[xPermsMgr] Set " . $target->getName() . "'s rank successfully.");
						
						if($target instanceof Player)
						{
							$target->sendMessage("[xPermsMgr] " . $message);
						}
					}
					else
					{
						$sender->sendMessage("[xPermsMgr] ERROR: Invalid Group!");
					}		
					
					break;
						
				case "users":
					
					if(count($args) > 3)
					{
						$sender->sendMessage("[xPermsMgr] Usage: /xpmgr users <GROUP_NAME>");
							
						break;
					}
						
					if(isset($args[1]) and $this->isValidGroup($args[1]))
					{
						foreach($this->getAllUserConfigFiles() as $cfg_file)
						{
							$user_cfg = new Config($this->getDataFolder() . "players/" . $cfg_file, Config::YAML, array(
							));
							
							if($user_cfg->get("group") == $args[1])
							{
								$output .= "[xPermsMgr] [" . $user_cfg->get("group") . "] ". $user_cfg->get("username") . "\n";
							}
						}
						
						if($output == "")
						{
							$sender->sendMessage("[xPermsMgr] There are no players in this group! \n" . $output);
							
							break;
						}
							
						$sender->sendMessage("[xPermsMgr] <-- ALL PLAYERS IN THIS GROUP --> \n" . $output);
						
						unset($user_cfg);
					}
					else
					{
						$sender->sendMessage("[xPermsMgr] ERROR: Invalid Group!");
					}
						
					break;
							
				default:
							
					$sender->sendMessage("[xPermsMgr] Usage: /xpmgr <groups / reload / setrank / users>");
			}
		}
		
		return true;
	}
	
	public function onDisable()
	{		
		console(TextFormat::RED . "[WARNING] xPermsMgr has been disabled.");
	}
}
