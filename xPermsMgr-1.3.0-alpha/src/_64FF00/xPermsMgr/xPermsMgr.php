<?php

namespace _64FF00\xPermsMgr;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

use pocketmine\permission\PermissibleBase;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class xPermsMgr extends PluginBase implements CommandExecutor
{
	public function onEnable()
	{
		@mkdir($this->getDataFolder() . "players/", 0777, true);
		
		$this->config = new xPMConfiguration($this);
		$this->groups = new xPMGroups($this);
		$this->users = new xPMUsers($this);
		
		$this->getServer()->getPluginManager()->registerEvents(new xPMListener($this), $this);
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
					
		foreach($this->getServer()->getOnlinePlayers() as $player)
		{
			$player->recalculatePermissions();
		}
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

					$sender->sendMessage(TF::DARK_GREEN . "[xPermsMgr] List of all groups: " . $output);
						
					break;
					
				case "reload":
				
					$this->reload();
							
					$sender->sendMessage(TF::DARK_GREEN . "[xPermsMgr] Successfully reloaded the config files and player permissions.");
							
					break;
						
				case "setrank":
					
					if(count($args) > 4)
					{
						$sender->sendMessage(TF::DARK_GREEN . "[xPermsMgr] Usage: /xpmgr setrank <USER_NAME> <GROUP_NAME>");
							
						break;
					}
					
					if(!isset($args[1]))
					{
						$sender->sendMessage(TF::RED . "[xPermsMgr] ERROR: Invalid Player.");
						
						break;
					}
					
					$target = $this->getValidPlayer($args[1]);
					
					if(!isset($args[2]))
					{
						$sender->sendMessage(TF::RED . "[xPermsMgr] ERROR: Invalid Group.");
						
						break;
					}
					
					$group = $this->groups->isValidGroup($args[2]) ? $args[2] : $this->groups->getByAlias($args[2]);
					
					$this->users->setGroup($target, $group);
												
					$message = str_replace("{RANK}", strtolower($group), $this->config->getConfig()["message-on-rank-change"]);
								
					$sender->sendMessage(TF::DARK_GREEN . "[xPermsMgr] Set " . $target->getName() . "'s rank successfully.");
						
					if($target instanceof Player)
					{
						$target->sendMessage(TF::DARK_GREEN . "[xPermsMgr] " . $message);
					}		
					
					break;
						
				case "users":
					
					if(count($args) > 3)
					{
						$sender->sendMessage(TF::DARK_GREEN . "[xPermsMgr] Usage: /xpmgr users <GROUP_NAME>");
							
						break;
					}
					
					if(!isset($args[1]))
					{
						$sender->sendMessage(TF::RED . "[xPermsMgr] ERROR: Invalid Group.");
						
						break;
					}
					
					$group = $this->groups->isValidGroup($args[1]) ? $args[1] : $this->groups->getByAlias($args[1]);

					foreach($this->users->getAll() as $filename)
					{
						$user_cfg = $this->users->getConfig($filename);
							
						if($user_cfg->get("group") == $group)
						{
								$output .= "[xPermsMgr] [" . $user_cfg->get("group") . "] ". $user_cfg->get("username") . "\n";
						}
					}
						
					if($output == "")
					{
						$sender->sendMessage(TF::YELLOW . "[xPermsMgr] There are no players in this group! \n");
							
						break;
					}
							
					$sender->sendMessage(TF::DARK_AQUA . "[xPermsMgr] <-- ALL PLAYERS IN THIS GROUP :D --> \n \n" . TF::AQUA . $output);
						
					unset($user_cfg);
						
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
