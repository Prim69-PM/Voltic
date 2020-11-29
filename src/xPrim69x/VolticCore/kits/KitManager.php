<?php

namespace xPrim69x\VolticCore\kits;

use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\Player;
use xPrim69x\VolticCore\Main;

class KitManager {

	private $main;
	public $kits = [];

	public function __construct(Main $main){
		$this->main = $main;
	}

	public function getKits(){
		return $this->kits;
	}

	public function loadKits(){
		$kits = yaml_parse_file($this->main->getDataFolder() . 'kits.yml');
		foreach($kits as $name => $data){
			$this->kits[$name] = new Kit($this->main, $name, $data);
		}
	}

	public function getKit(string $name) : ?Kit{
		$lowerKeys = array_change_key_case($this->kits, 0);
		if(isset($lowerKeys[strtolower($name)])){
			return $lowerKeys[strtolower($name)];
		}
		return null;
	}

}