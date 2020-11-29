<?php

namespace xPrim69x\VolticCore;

use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\kits\Kit;
use xPrim69x\VolticCore\utils\SimpleForm;

class Forms {

	public function kitui(Player $player, array $kits){
		$form = new SimpleForm(function (Player $player, ?string $data = null){
			if($data === null) return true;
			$kit = Main::getInstance()->getKitManager()->getKit($data);
			if($kit === null) return true;
			$kit->handle($player);
			return true;
		});

		$form->setTitle("§l§cSelect a kit!");
		$form->setContent(TF::AQUA . "Kits: ");
		foreach($kits as $kit){
			if($kit instanceof Kit) $form->addButton($kit->getFormName() ?? $kit->getName(), -1, '', $kit->getName());
		}

		$player->sendForm($form);
		return $form;
	}

}