<?php

namespace xPrim69x\VolticCore\commands;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;

class PlayerVaultCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"playervault",
			TF::AQUA . "Access a private vault to store your items!",
			TF::RED . "Usage: " . TF::GRAY . "/pv <number>"
		);
		$this->setAliases(['pv']);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}
		$num = $args[0];
		if(!$sender->hasPermission("vault.$num")){
			$sender->sendMessage(TF::RED . 'You do not have access to that vault!');
			return;
		}
		$menu = InvMenu::create("invmenu:double_chest");
		$menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
			$player = $transaction->getPlayer();
			return $player->hasPermission('vault.edit.other') ? $transaction->continue() : $transaction->discard();
		})->setInventoryCloseListener(function (Player $player, Inventory $inv) use ($num){
			$v = json_encode($inv);
			$v = bin2hex($v);
			$this->main->getDBClass()->save($player, $num, $v);
		});
		$menu->setName(TF::BOLD . TF::RED . "Vault $num");
		$inventory = $menu->getInventory();
		$vault = $this->main->getDBClass()->getVault($sender, $num);
		$inventory->setContents(json_decode(hex2bin($vault)));
		$menu->send($sender);
	}

}