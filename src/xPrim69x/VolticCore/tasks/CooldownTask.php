<?php

namespace xPrim69x\VolticCore\tasks;

use pocketmine\scheduler\Task;
use xPrim69x\VolticCore\kits\KitManager;

class CooldownTask extends Task {

	private $manager;

	public function __construct(KitManager $manager){
		$this->manager = $manager;
	}

	public function onRun(int $currentTick){
		foreach($this->manager->kits as $kit){
			$kit->processCD();
		}
	}

}