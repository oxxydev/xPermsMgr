<?php

namespace _64FF00\xPermsMgr;

use pocketmine\utils\Config;

class xPMConfiguration
{
	private $config;
	
	public function __construct(xPermsMgr $plugin)
	{
		$this->plugin = $plugin;
		
		$this->load();
	}
	
	public function load()
	{
		if(!(file_exists($this->plugin->getDataFolder() . "config.yml")))
		{
			$this->plugin->saveDefaultConfig();
		}
		
		$this->config = $this->plugin->getConfig();
	}
	
	public function getConfig()
	{
		return $this->config->getAll();
	}
}