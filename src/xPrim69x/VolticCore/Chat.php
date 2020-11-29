<?php

namespace xPrim69x\VolticCore;

use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class Chat {

	private $main;

	public function __construct(Main $main){
		$this->main = $main;
	}

	public function getFormat(Player $player, string $message){
		$def = json_decode(file_get_contents($this->main->getDataFolder() . "ranks.json"), true);
		$rank = $this->main->getDBClass()->getRank($player);
		$df = $def[$rank]["chat"];
		return $this->translate($df, $player, $message);
	}

	public function translate(string $format, Player $player, string $message){
		if (!$player->hasPermission("pchat.coloredMessages")) {
			$format = str_replace("{msg}", $this->strip($message), $format);
		} else {
			$format = str_replace("{msg}", $message, $format);
		}
		$format = str_replace("{name}", $player->getName(), $format);
		return $format;
	}

	public function strip(string $string){
		$string = str_replace(TF::BLACK, '', $string);
		$string = str_replace(TF::DARK_BLUE, '', $string);
		$string = str_replace(TF::DARK_GREEN, '', $string);
		$string = str_replace(TF::DARK_AQUA, '', $string);
		$string = str_replace(TF::DARK_RED, '', $string);
		$string = str_replace(TF::DARK_PURPLE, '', $string);
		$string = str_replace(TF::GOLD, '', $string);
		$string = str_replace('§g', '', $string);
		$string = str_replace(TF::GRAY, '', $string);
		$string = str_replace(TF::DARK_GRAY, '', $string);
		$string = str_replace(TF::BLUE, '', $string);
		$string = str_replace(TF::GREEN, '', $string);
		$string = str_replace(TF::AQUA, '', $string);
		$string = str_replace(TF::RED, '', $string);
		$string = str_replace(TF::LIGHT_PURPLE, '', $string);
		$string = str_replace(TF::YELLOW, '', $string);
		$string = str_replace(TF::WHITE, '', $string);
		$string = str_replace(TF::OBFUSCATED, '', $string);
		$string = str_replace(TF::BOLD, '', $string);
		$string = str_replace(TF::STRIKETHROUGH, '', $string);
		$string = str_replace(TF::UNDERLINE, '', $string);
		$string = str_replace(TF::ITALIC, '', $string);
		$string = str_replace(TF::RESET, '', $string);
		return $string;
	}

}