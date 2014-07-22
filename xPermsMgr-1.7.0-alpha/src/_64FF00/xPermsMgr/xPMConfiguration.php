<?php

namespace _64FF00\xPermsMgr;

use pocketmine\utils\Config;

class xPMConfiguration
{
	private $config;
	
	public function __construct(xPermsMgr $plugin)
	{
		$this->plugin = $plugin;
		
		$this->reload();
	}
	
	public function reload()
	{
		if(!(file_exists($this->plugin->getDataFolder() . "config.yml")))
		{
			$this->plugin->saveDefaultConfig();
		}
		
		$this->config = $this->plugin->getConfig();
		
		if(!$this->config->get("chat-format"))
		{
			$this->config->set("chat-format", "<{PREFIX} {USER_NAME}> {MESSAGE}");
		}
		
		if(!$this->config->get("custom-nametag"))
		{
			$this->config->set("custom-nametag", "<{PREFIX} {USER_NAME}>");
		}
		
		if(!$this->config->get("enable-op-override"))
		{
			$this->config->set("enable-op-override", true);
		}
		
		if(!$this->config->get("message-on-insufficient-build-permission"))
		{
			$this->config->set("message-on-insufficient-build-permission", "You don't have permission to build here.");
		}
		
		if(!$this->config->get("message-on-insufficient-permissions"))
		{
			$this->config->set("message-on-insufficient-permissions", "I don't think you have permission to do this...");
		}
		
		if(!$this->config->get("message-on-rank-change"))
		{
			$this->config->set("message-on-rank-change", "Your rank has been changed into a / an {RANK}!");
		}
	}
	
	public function getConfig()
	{
		return $this->config->getAll();
	}
}