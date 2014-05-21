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
		
		$this->config = new xPMConfiguration($this);
		$this->groups = new xPMGroups($this);
		
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
		if($this->config->getConfig()["chat-format"] != null)
		{
			$format = str_replace("{PREFIX}", $this->groups->getPrefix($this->getPlayerRank($event->getPlayer())), str_replace(
				"{USER_NAME}", $event->getPlayer()->getName(), str_replace(
					"{SUFFIX}", $this->groups->getSuffix($this->getPlayerRank($event->getPlayer())), str_replace(
						"{MESSAGE}", $event->getMessage(), $this->config->getConfig()["chat-format"]
						)
					)
				)
			);
		}
		else
		{
			$format = "<" . $event->getPlayer()->getName() . "> " . $event->getMessage();
		}
		
		$event->setFormat($format);
	}
	
	public function onPlayerSpawn(PlayerSpawnEvent $event)
	{
		$this->setPlayerPermissions($event->getPlayer());
	}
	
	private function getAllPlayerPermissions($player)
	{
		$inherited_group = $this->groups->getGroup($this->getPlayerRank($player))["inheritance"];
		
		$perms = $this->groups->getGroup($this->getPlayerRank($player))["permissions"];
		
		if(isset($inherited_group) and is_array($inherited_group))
		{
			foreach($inherited_group as $ig)
			{
				if($this->groups->isValidGroup($ig) != null)
				{
					$perms = array_merge($perms, $this->groups->getGroup($ig)["permissions"]);
				}
			}
		}
		
		return $perms;
	}
	
	private function getAllUserConfigFiles()
	{
		return array_diff(scandir($this->getDataFolder() . "players/"), array(".", "..", ""));
	}
	
	private function getPlayerRank($player)
	{
		$cfg = $this->getUserConfig($player);
		
		return $cfg->getAll()["group"];
	}
	
	private function getUserConfig($player)
	{
		$username = $player->getName();
		
		if(!(file_exists($this->getDataFolder() . "players/" . strtolower($username) . ".yml")))
		{
			return new Config($this->getDataFolder() . "players/" . strtolower($username) . ".yml", Config::YAML, array(
				"username" => $username,
				"group" => $this->groups->getDefaultGroup(),
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
		if($this->groups->isValidGroup($groupName))
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
					
					foreach($this->groups->getAllGroups() as $group)
					{
						$output .= $group . ", ";
					}

					$output = substr($output, 0, -2);

					$sender->sendMessage("[xPermsMgr] List of all groups: " . $output);
						
					break;
					
				case "reload":
				
					@mkdir($this->getDataFolder() . "players/", 0777, true);
				
					$this->config->load();
					$this->groups->load();
					
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
						
					if(isset($args[2]) and $this->groups->isValidGroup($args[2]))
					{					
						$this->setPlayerRank($target, $args[2]);
						
						$this->setPlayerPermissions($target);
							
						$message = str_replace("{RANK}", strtolower($args[2]), $this->config->getConfig()["message-on-rank-change"]);
								
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
						
					if(isset($args[1]) and $this->groups->isValidGroup($args[1]))
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