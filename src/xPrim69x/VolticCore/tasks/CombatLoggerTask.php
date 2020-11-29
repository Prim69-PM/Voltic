<?php

namespace xPrim69x\VolticCore\tasks;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use xPrim69x\VolticCore\Main;

class CombatLoggerTask extends Task {

	private $main;

	public function __construct(Main $main){
		$this->main = $main;
	}

	public function onRun(int $currentTick) : void{
		foreach($this->main->combat as $name => $time){
			$time--;
			if($time <= 0){
				$this->main->getUtils()->setTagged($name, false);
				$player = $this->main->getServer()->getPlayer($name);
				if($player instanceof Player){
					$player->sendMessage($this->main->getConfig()->get("combat-leave"));
				}
				return;
			}
			$this->main->combat[$name]--;
		}
	}

}