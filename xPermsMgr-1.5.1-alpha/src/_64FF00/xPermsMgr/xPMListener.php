<?php

namespace _64FF00\xPermsMgr;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;

use pocketmine\level\Level;

use pocketmine\utils\TextFormat as TF;

class xPMListener implements Listener
{
	public function __construct(xPermsMgr $plugin)
	{
		$this->config = new xPMConfiguration($plugin);
		$this->groups = new xPMGroups($plugin);
		$this->users = new xPMUsers($plugin);
		
		$this->plugin = $plugin;
	}
	
	public function onBlockBreak(BlockBreakEvent $event)
	{	
		if(!$event->getPlayer()->hasPermission("xpmgr.build"))
		{
			$event->getPlayer()->sendMessage(TF::RED . "[xPermsMgr] " . $this->config->getConfig()["message-on-insufficient-build-permission"]);
			
			$event->setCancelled(true);
		}
	}
	
	public function onBlockPlace(BlockPlaceEvent $event)
	{	
		if(!$event->getPlayer()->hasPermission("xpmgr.build"))
		{
			$event->getPlayer()->sendMessage(TF::RED . "[xPermsMgr] " . $this->config->getConfig()["message-on-insufficient-build-permission"]);
			
			$event->setCancelled(true);
		}
	}
	
	public function onLevelChange(EntityLevelChangeEvent $event)
	{
		if($event->getEntity() instanceof Player)
		{
			$this->users->setPermissions($event->getEntity(), $event->getTarget());
		
			$this->users->setNameTag($event->getEntity(), $event->getTarget());
		}
	}
	
	public function onPlayerChat(PlayerChatEvent $event)
	{
		$group = $this->users->getGroup($event->getPlayer(), $event->getPlayer()->getLevel());
		
		if($this->config->getConfig()["chat-format"] != null)
		{
			$format = str_replace("{PREFIX}", $this->groups->getGroupPrefix($group), str_replace(
				"{USER_NAME}", $event->getPlayer()->getName(), str_replace(
					"{SUFFIX}", $this->groups->getGroupSuffix($group), str_replace(
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
		$this->users->setPermissions($event->getPlayer(), $event->getPlayer()->getLevel());
		
		$this->users->setNameTag($event->getPlayer(), $event->getPlayer()->getLevel());
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