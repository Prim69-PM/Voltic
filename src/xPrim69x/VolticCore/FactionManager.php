<?php

namespace xPrim69x\VolticCore;

use pocketmine\Player;

class FactionManager {

	private $main;
	public $invites = [];

	public function __construct(Main $main){
		$this->main = $main;
	}

	public function hasInvite(Player $player){
		$name = $player->getName();
		if(isset($this->invites[$name])) return true;
		return false;
	}

	public function addInvite(Player $player, string $faction){
		$name = $player->getName();
		$this->invites[$name] = $faction;
	}

	public function removeInvite(Player $player){
		$name = $player->getName();
		if($this->hasInvite($player)) unset($this->invites[$name]);
	}

	public function getFactionByInvite(Player $player){
		$name = $player->getName();
		if($this->hasInvite($player)) return $this->invites[$name];
		return null;
	}



}