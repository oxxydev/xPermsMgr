<?php

namespace _64FF00\xPermsMgr;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;

class xPMListener implements Listener
{
	public function __construct(xPermsMgr $plugin)
	{
		$this->config = new xPMConfiguration($plugin);
		$this->groups = new xPMGroups($plugin);
		$this->users = new xPMUsers($plugin);
		
		$this->plugin = $plugin;
	}
	
	public function onPlayerChat(PlayerChatEvent $event)
	{
		$prefix = $this->groups->getPrefix($this->users->getCurrentGroup($event->getPlayer()));
		
		$suffix = $this->groups->getSuffix($this->users->getCurrentGroup($event->getPlayer()));
		
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
			$this->plugin->getLogger()->alert("Invalid chat-format given, using the default one");
			
			$format = "<" . $event->getPlayer()->getName() . "> " . $event->getMessage();
		}
		
		$event->setFormat($format);
	}
	
	public function onPlayerJoin(PlayerJoinEvent $event)
	{	
		$this->users->setPermissions($event->getPlayer());
	}
	
	public function onPlayerKick(PlayerKickEvent $event)
	{	
		$event->getPlayer()->removeAttachment($this->users->getAttachment($event->getPlayer()));
	}
	
	public function onPlayerQuit(PlayerQuitEvent $event)
	{	
		$event->getPlayer()->removeAttachment($this->users->getAttachment($event->getPlayer()));
	}
}