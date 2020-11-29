<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;
use xPrim69x\VolticCore\Utils;

class AddRankCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"addrank",
			TF::AQUA . "Create a rank!",
			TF::RED . "Usage: " . TF::GRAY . "/addrank <rank>"
		);
		$this->setPermission("addrank.command");
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission("addrank.command")){
			$sender->sendMessage(TF::DARK_RED . "You do not have permission to execute this command!");
			return;
		}
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}
		$rank = $args[0];
		$ranks = json_decode(file_get_contents($this->main->getDataFolder() . "ranks.json"), true);
		if(isset($ranks[$rank])){
			$sender->sendMessage(TF::RED . "That rank already exists!");
			return;
		}
		if(!Utils::validName($rank)){
			$sender->sendMessage(TF::RED . "That is not a valid rank name!");
			return;
		}
		$ranks[$rank] = [
			"inherits" => [],
			"permissions" => [],
			"nametag" => "§7[$rank] §f{name}",
			"chat" => "§7[$rank] §f{name}: {msg}"
		];
		file_put_contents($this->main->getDataFolder() . "ranks.json", json_encode($ranks, JSON_PRETTY_PRINT));
		$sender->sendMessage(TF::GREEN . "You have created the rank " . TF::WHITE . "$rank!");
	}

}