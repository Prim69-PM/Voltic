<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class SpawnCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"spawn",
			TF::AQUA . "Teleport to spawn!",
		);
		$this->setAliases(['hub']);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;
		$sender->teleport($this->main->getServer()->getDefaultLevel()->getSafeSpawn());
		$sender->sendMessage(TF::AQUA . 'You have been teleported to spawn!');
	}

}