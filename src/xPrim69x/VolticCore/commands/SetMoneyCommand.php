<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class SetMoneyCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"setmoney",
			TF::AQUA . "Set another players balance!",
			TF::RED . "Usage: " . TF::GRAY . "/setmoney <player> <money>"
		);
		$this->setPermission("setmoney.command");
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission("setmoney.command")){
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
		$this->main->getDBClass()->setMoney($player, $amount);
		$sender->sendMessage(TF::GREEN . "You have set " . TF::WHITE . "$name's " . TF::GREEN . "money to $$amount!");
	}

}