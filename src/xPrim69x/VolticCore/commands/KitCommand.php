<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Forms;
use xPrim69x\VolticCore\Main;

class KitCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"kit",
			TF::AQUA . "Claim a kit!",
		);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player){
			$sender->sendMessage(TF::DARK_RED . "Use this command in-game!");
			return;
		}
		$forms = new Forms();
		$kits = $this->main->getKitManager()->getKits();
		$forms->kitui($sender, $kits);
	}

}