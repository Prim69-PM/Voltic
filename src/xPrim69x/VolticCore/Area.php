<?php

namespace xPrim69x\VolticCore;

use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Area {

	private $name;
	private $pos1;
	private $pos2;
	private $level;
	private $main;

	public function __construct(string $name, Vector3 $pos1, Vector3 $pos2, string $level, Main $main){
		$this->name = $name;
		$this->pos1 = $pos1;
		$this->pos2 = $pos2;
		$this->level = $level;
		$this->main = $main;
		$this->add();
	}

	public function getName() : string{
		return $this->name;
	}

	public function getPos1() : Vector3{
		return $this->pos1;
	}

	public function getPos2() : Vector3{
		return $this->pos2;
	}

	public function getLevel() : ?Level{
		return $this->main->getServer()->getLevelByName($this->level);
	}

	public function isIn(Player $player) : bool{
		$area = new AxisAlignedBB(min($this->pos1->getX(), $this->pos2->getX()), min($this->pos1->getY(), $this->pos2->getY()), min($this->pos1->getZ(), $this->pos2->getZ()), max($this->pos1->getX(), $this->pos2->getX()), max($this->pos1->getY(), $this->pos2->getY()), max($this->pos1->getZ(), $this->pos2->getZ()));
		return $area->isVectorInside($player);
	}

	public function add(){
		$this->main->areas[$this->name] = $this;
	}

	public function delete(){
		if(isset($this->main->areas[$this->name])){
			unset($this->main->areas[$this->name]);
			$this->main->saveAreas();
		}
	}

}