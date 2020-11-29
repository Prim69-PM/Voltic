<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class RankListCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"ranklist",
			TF::AQUA . "Sends you the list of existing ranks!",
		);
		$this->setPermission("ranklist.command");
		$this->setAliases(["ranks"]);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission("ranklist.command")){
			$sender->sendMessage(TF::DARK_RED . "You do not have permission to execute this command!");
			return;
		}
		$ranks = json_decode(file_get_contents($this->main->getDataFolder() . "ranks.json"), true);
		$rs = implode(', ', array_keys($ranks));
		$rc = count($ranks);
		$sender->sendMessage(TF::GREEN . "Ranks ($rc): $rs");
	}

}