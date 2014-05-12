<?php

namespace _64FF00\xPermsMgr;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;

class xPermsMgr extends PluginBase implements CommandExecutor, Listener
{
	public function onEnable()
	{
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
	
	private function xPermsMgrCommand(CommandSender $sender, Command $cmd, $label, array $args)
	{
	}
	
	public function onDisable()
	{		
		console(TextFormat::RED . "[WARNING] xPermsMgr has been disabled.");
	}
}
