<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class PayCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"pay",
			TF::AQUA . "Pay money to another player!",
			TF::RED . "Usage: " . TF::GRAY . "/pay <player> <amount>"
		);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;
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
		if($amount < 0){
			$sender->sendMessage(TF::RED . "You cannot pay a negative amount!");
			return;
		}
		$db = $this->main->getDBClass();
		if($db->hasMoney($player, $amount)){
			$sender->sendMessage(TF::RED . "You do not have enough money!");
			return;
		}
		if($player->getName() === $sender->getName()){
			$sender->sendMessage(TF::RED . "You cannot pay yourself!");
			return;
		}
		$db->addMoney($player, $amount);
		$db->removeMoney($sender, $amount);
		$cbal = $db->getMoney($sender);
		$player->sendMessage(TF::GREEN . "You have received $$amount from {$sender->getName()}!");
		$sender->sendMessage(TF::GREEN . "You have sent $$amount to {$player->getName()}. You now have $$cbal.");
	}

}