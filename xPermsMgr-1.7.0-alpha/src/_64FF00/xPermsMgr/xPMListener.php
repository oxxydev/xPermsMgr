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
		$player = $event->getPlayer();
		
		if(!$player->hasPermission("xpmgr.build"))
		{
			$player->sendMessage(TF::RED . "[xPermsMgr] " . $this->config->getConfig()["message-on-insufficient-build-permission"]);
			
			$event->setCancelled(true);
		}
	}
	
	public function onBlockPlace(BlockPlaceEvent $event)
	{	
		$player = $event->getPlayer();
		
		if(!$player->hasPermission("xpmgr.build"))
		{
			$player->sendMessage(TF::RED . "[xPermsMgr] " . $this->config->getConfig()["message-on-insufficient-build-permission"]);
			
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
		$player = $event->getPlayer();
		
		$group = $this->users->getGroup($player, $player->getLevel());
		
		if($this->config->getConfig()["chat-format"] != null)
		{
			$format = str_replace("{PREFIX}", $this->groups->getGroupPrefix($group), str_replace(
				"{USER_NAME}", $player->getName(), str_replace(
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
			
			$format = "<" . $player->getName() . "> " . $event->getMessage();
		}
		
		$event->setFormat($format);
	}
	
	public function onPlayerJoin(PlayerJoinEvent $event)
	{
		$player = $event->getPlayer();
		
		$this->users->setPermissions($player, $player->getLevel());	
		
		$this->users->setNameTag($player, $player->getLevel());		
	}
	
	public function onPlayerKick(PlayerKickEvent $event)
	{	
		$player = $event->getPlayer();
		
		$player->removeAttachment($this->users->getAttachment($player));
	}
	
	public function onPlayerQuit(PlayerQuitEvent $event)
	{	
		$player = $event->getPlayer();
		
		$player->removeAttachment($this->users->getAttachment($player));
	}
}