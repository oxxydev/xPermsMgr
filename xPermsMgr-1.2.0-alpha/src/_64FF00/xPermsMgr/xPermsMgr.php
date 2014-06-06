<?php

namespace _64FF00\xPermsMgr;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;

use pocketmine\permission\PermissibleBase;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class xPermsMgr extends PluginBase implements CommandExecutor, Listener
{
	public function onEnable()
	{
		@mkdir($this->getDataFolder() . "players/", 0777, true);
		
		$this->config = new xPMConfiguration($this);
		$this->groups = new xPMGroups($this);
		$this->users = new xPMUsers($this);
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		$this->getLogger()->info("xPermsMgr has been enabled! :D");
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
	
	public function onPlayerJoin(PlayerJoinEvent $event)
	{
		$this->users->removeAttachment($event->getPlayer());
		
		$event->getPlayer()->recalculatePermissions();
	}
	
	public function onPlayerChat(PlayerChatEvent $event)
	{
		$prefix = $this->groups->getPrefix($this->users->getGroup($event->getPlayer()));
		
		$suffix = $this->groups->getSuffix($this->users->getGroup($event->getPlayer()));
		
		if($this->config->getConfig()["chat-format"] != null)
		{
			$format = str_replace("{PREFIX}", $prefix, str_replace(
				"{USER_NAME}", $event->getPlayer()->getName(), str_replace(
					"{SUFFIX}", $suffix, str_replace(
						"{MESSAGE}", $event->getMessage(), $this->config->getConfig()["chat-format"]
						)
					)
				)
			);
		}
		else
		{
			$this->getLogger()->alert("Invalid chat-format given, using the default one");
			
			$format = "<" . $event->getPlayer()->getName() . "> " . $event->getMessage();
		}
		
		$event->setFormat($format);
	}
	
	private function getValidPlayer($username)
	{
		$player = $this->getServer()->getPlayer($username);
		
		return $player instanceof Player ? $player : $this->getServer()->getOfflinePlayer($username);
	}
	
	private function reload()
	{
		@mkdir($this->getDataFolder() . "players/", 0777, true);
				
		$this->config->load();
		$this->groups->load();
					
		$this->users->recalculatePerms();
	}
	
	private function xPermsMgrCommand(CommandSender $sender, Command $cmd, $label, array $args)
	{
		if(!isset($args[0]))
		{
			$sender->sendMessage(TF::GREEN . "[xPermsMgr] xPermsMgr v" . $this->getDescription()->getVersion() . " by " . $this->getDescription()->getAuthors()[0] . "!");
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

					$sender->sendMessage(TF::GREEN . "[xPermsMgr] List of all groups: " . $output);
						
					break;
					
				case "reload":
				
					$this->reload();
							
					$sender->sendMessage(TF::DARK_GREEN . "[xPermsMgr] Successfully reloaded the config files and player permissions.");
							
					break;
						
				case "setrank":
					
					if(count($args) > 4)
					{
						$sender->sendMessage(TF::GREEN . "[xPermsMgr] Usage: /xpmgr setrank <USER_NAME> <GROUP_NAME>");
							
						break;
					}
					
					if(!isset($args[1]))
					{
						$sender->sendMessage(TF::RED . "[xPermsMgr] ERROR: Invalid Player!");
						
						break;
					}
					
					$target = $this->getValidPlayer($args[1]);
					
					if(isset($args[2]))
					{
						$group = $this->groups->isValidGroup($args[2]) ? $args[2] : $this->groups->getByAlias($args[2]);
					}
						
					if(isset($group))
					{					
						$this->users->setGroup($target, $group);
												
						$message = str_replace("{RANK}", strtolower($group), $this->config->getConfig()["message-on-rank-change"]);
								
						$sender->sendMessage(TF::GREEN . "[xPermsMgr] Set " . $target->getName() . "'s rank successfully.");
						
						if($target instanceof Player)
						{
							$target->sendMessage(TF::GREEN . "[xPermsMgr] " . $message);
						}
					}
					else
					{
						$sender->sendMessage(TF::RED . "[xPermsMgr] ERROR: Invalid Group!");
					}		
					
					break;
						
				case "users":
					
					if(count($args) > 3)
					{
						$sender->sendMessage(TF::GREEN . "[xPermsMgr] Usage: /xpmgr users <GROUP_NAME>");
							
						break;
					}
					
					if(isset($args[1]))
					{
						$group = $this->groups->isValidGroup($args[1]) ? $args[1] : $this->groups->getByAlias($args[1]);
					};
						
					if(isset($group))
					{
						foreach($this->users->getAll() as $cfg_file)
						{
							$user_cfg = new Config($this->getDataFolder() . "players/" . $cfg_file, Config::YAML, array(
							));
							
							if($user_cfg->get("group") == $group)
							{
								$output .= "[xPermsMgr] [" . $user_cfg->get("group") . "] ". $user_cfg->get("username") . "\n";
							}
						}
						
						if($output == "")
						{
							$sender->sendMessage(TF::YELLOW . "[xPermsMgr] There are no players in this group! \n" . $output);
							
							break;
						}
							
						$sender->sendMessage(TF::GREEN . "[xPermsMgr] <-- ALL PLAYERS IN THIS GROUP! :D --> \n" . $output);
						
						unset($user_cfg);
					}
					else
					{
						$sender->sendMessage(TF::RED . "[xPermsMgr] ERROR: Invalid Group!");
					}
						
					break;
							
				default:
							
					$sender->sendMessage(TF::DARK_GREEN . "[xPermsMgr] Usage: /xpmgr <groups / reload / setrank / users>");
			}
		}
		
		return true;
	}
	
	public function onDisable()
	{		
		$this->getLogger()->warning("xPermsMgr has been disabled.");
	}
}