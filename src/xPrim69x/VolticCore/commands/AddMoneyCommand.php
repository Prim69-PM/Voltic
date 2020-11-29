<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class AddMoneyCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"addmoney",
			TF::AQUA . "Add money onto another players balance!",
			TF::RED . "Usage: " . TF::GRAY . "/addmoney <player> <money>"
		);
		$this->setPermission("addmoney.command");
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission("addmoney.command")){
			$sender->sendMessage(TF::DARK_RED . "You do not have permission to execute this command!");
			return;
		}
		if(count($args) < 2){
			$sender->sendMessage($this->usageMessage);
			return;
		}
		$amount = $args[1];
		$player = $this->main->getServer()->getPlayer($args[0]);
		if($player === null){
			$sender->sendMessage(TF::RED . "That player is not online!");
			return;
		}
		if(!is_numeric($amount)){
			$sender->sendMessage(TF::RED . "The amount must be a number!");
			return;
		}
		$name = $player->getName();
		$this->main->getDBClass()->addMoney($player, $amount);
		$sender->sendMessage(TF::GREEN . "You have added $$amount onto " . TF::WHITE . "$name's " . TF::GREEN . "balance!");
	}

}