<?php

namespace xPrim69x\VolticCore;

use pocketmine\Player;

class Utils {

	const SLIMECD = 1;
	const PEARLCD = 2;
	const GAPPLECD = 3;

	public static function validName(string $name){
		return preg_match('/[0-9a-zA-Z\xA1-\xFE]$/', $name);
	}

	public static function hasCooldown(Player $player, int $type){
		$main = Main::getInstance();
		$name = $player->getName();
		if(isset($main->cooldown[$type][$name]) && time() - $main->cooldown[$type][$name] < $main->getConfig()->get("slime-cooldown"))
			return true;
		self::removeCooldown($player, $type);
		return false;
	}

	public static function addCooldown(Player $player, int $type){
		$name = $player->getName();
		$main = Main::getInstance();
		if(!isset($main->cooldown[$type][$name]))
			Main::getInstance()->cooldown[$type][$name] = time();
	}

	public static function removeCooldown(Player $player, int $type){
		$name = $player->getName();
		if(isset(Main::getInstance()->cooldown[$type][$name]))
			unset(Main::getInstance()->cooldown[$type][$name]);
	}

	public static function getCooldown(Player $player, int $type){
		$main = Main::getInstance();
		if(isset($main->cooldown[$type][$player->getName()]))
			return $main->getConfig()->get("slime-cooldown") - (time() - $main->cooldown[$type][$player->getName()]);
		return 0;
	}

	public static function addToArray(Player $player) : void{
		$main = Main::getInstance();
		$name = $player->getLowerCaseName();
		if(!isset($main->clicks[$name])) $main->clicks[$name] = [];
	}

	public static function removeFromArray(Player $player) : void{
		$main = Main::getInstance();
		$name = $player->getLowerCaseName();
		if(isset($main->clicks[$name])) unset($main->clicks[$name]);
	}

	public function addClick(Player $player){
		$main = Main::getInstance();
		if(isset($main->clicks[$player->getLowerCaseName()])){
			array_unshift($main->clicks[$player->getLowerCaseName()], microtime(true));
			if(count($main->clicks[$player->getLowerCaseName()]) >= 100)
				array_pop($main->clicks[$player->getLowerCaseName()]);
		}
	}

	public function getClicks(Player $player, float $dt = 1.0, int $rp = 1){
		$main = Main::getInstance();
		$name = $player->getLowerCaseName();
		if(!isset($main->clicks[$name]) || empty($main->clicks[$name])) return 0.0;
		$mt = microtime(true);
		return round(count(array_filter($main->clicks[$name], static function (float $t) use ($dt, $mt) : bool {
			return (($mt - $t) <= $dt);
		})) / $dt, $rp);
	}

	public function setTagged($player, bool $value = true, int $time = 15){
		if($player instanceof Player) $player = $player->getName();
		if($value){
			Main::getInstance()->combat[$player] = $time;
			return;
		}
		unset(Main::getInstance()->combat[$player]);
	}

	public function isTagged($player){
		if($player instanceof Player) $player = $player->getName();
		return isset(Main::getInstance()->combat[$player]);
	}

	public function getLength($player){
		if($player instanceof Player) $player = $player->getName();
		return $this->isTagged($player) ? Main::getInstance()->combat[$player] : 0;
	}

}