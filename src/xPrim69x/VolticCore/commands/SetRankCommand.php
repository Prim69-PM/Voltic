<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class SetRankCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"setrank",
			TF::AQUA . "Set another players rank!",
			TF::RED . "Usage: " . TF::GRAY . "/setrank <player> <rank>"
		);
		$this->setPermission("setrank.command");
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission("setrank.command")){
			$sender->sendMessage(TF::DARK_RED . "You do not have permission to execute this command!");
			return;
		}
		if(count($args) < 2){
			$sender->sendMessage($this->usageMessage);
			return;
		}
		$rank = $args[1];
		$player = $this->main->getServer()->getPlayer($args[0]);
		if($player === null){
			$sender->sendMessage(TF::RED . "That player is not online!");
			return;
		}
		$name = $player->getName();
		$ranks = json_decode(file_get_contents($this->main->getDataFolder() . "ranks.json"), true);
		if(!isset($ranks[$rank])){
			$sender->sendMessage(TF::RED . "The rank $rank does not exist!");
			return;
		}
		$this->main->getDBClass()->setRank($player, $rank);
		$sender->sendMessage(TF::GREEN . "You have set " . TF::WHITE . "$name's " . TF::GREEN . "rank to $rank!");
	}

}