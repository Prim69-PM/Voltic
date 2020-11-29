<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class RemoveMoneyCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"removemoney",
			TF::AQUA . "Remove money from another players balance!",
			TF::RED . "Usage: " . TF::GRAY . "/removemoney <player> <money>"
		);
		$this->setPermission("removemoney.command");
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission("removemoney.command")){
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
		$db = $this->main->getDBClass();
		if($amount > $db->getMoney($player)) $amount = $db->getMoney($player);
		$name = $player->getName();
		$db->removeMoney($player, $amount);
		$sender->sendMessage(TF::GREEN . "You have removed $$amount from " . TF::WHITE . "$name's " . TF::GREEN . "balance!");
	}

}