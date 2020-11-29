<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class BalanceCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"balance",
			TF::AQUA . "View your own or another players balance!",
			TF::RED . "Usage: " . TF::GRAY . "/bal [player]"
		);
		$this->setAliases(['money', 'bal']);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;
		$db = $this->main->getDBClass();
		if(count($args) < 1){
			$balance = $db->getMoney($sender);
			$sender->sendMessage(TF::GREEN . "Your balance is $$balance!");
			return;
		}
		$player = $this->main->getServer()->getPlayer($args[0]);
		if($player === null){
			$sender->sendMessage(TF::RED . "That player is not online!");
			return;
		}
		$pbal = $db->getMoney($player);
		$name = $player->getName();
		$sender->sendMessage("$name's" . TF::GREEN . " balance is $$pbal!");
	}

}