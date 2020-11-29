<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class DelRankCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"delrank",
			TF::AQUA . "Delete a rank!",
			TF::RED . "Usage: " . TF::GRAY . "/delrank <rank>"
		);
		$this->setPermission("delrank.command");
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission("delrank.command")){
			$sender->sendMessage(TF::DARK_RED . "You do not have permission to execute this command!");
			return;
		}
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}
		$rank = $args[0];
		$ranks = json_decode(file_get_contents($this->main->getDataFolder() . "ranks.json"), true);
		if(!isset($ranks[$rank])){
			$sender->sendMessage(TF::RED . "That rank does not exist!");
			return;
		}
		if($rank === $this->main->getConfig()->get("default-rank")){
			$sender->sendMessage(TF::RED . "You cannot delete the default rank!");
			return;
		}
		unset($ranks[$rank]);
		file_put_contents($this->main->getDataFolder() . "ranks.json", json_encode($ranks, JSON_PRETTY_PRINT));
		$sender->sendMessage(TF::GREEN . "You have successfully deleted the rank " . TF::WHITE . "$rank.");
	}

}