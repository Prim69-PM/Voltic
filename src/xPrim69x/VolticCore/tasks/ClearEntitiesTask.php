<?php

namespace xPrim69x\VolticCore\tasks;

use pocketmine\entity\object\ItemEntity;
use pocketmine\scheduler\Task;
use xPrim69x\VolticCore\Main;

class ClearEntitiesTask extends Task {

	private $main;

	public function __construct(Main $main){
		$this->main = $main;
	}

	public function onRun(int $currentTick) : void{
		if(count($this->main->getServer()->getOnlinePlayers()) < 1) return;
		$cleared = 0;
		foreach ($this->main->getServer()->getLevels() as $level) {
			foreach ($level->getEntities() as $entity) {
				if($entity instanceof ItemEntity){
					$entity->flagForDespawn();
					++$cleared;
				}
			}
		}
		if($this->main->getConfig()->get("broadcast-cleared")){
			$msg = $this->main->getConfig()->get("clear-entities-message");
			$msg = str_replace("{amount-cleared}", $cleared, $msg);
			$this->main->getServer()->broadcastMessage($msg);
		}
	}

}