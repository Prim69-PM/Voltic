<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Area;
use xPrim69x\VolticCore\Main;

class AreaCommand extends Command{

	private $main;

	public function __construct(Main $main){
		parent::__construct(
			"area",
			TF::AQUA . "Create a safe area where people can't pvp!",
			TF::RED . "Usage: " . TF::GRAY . "/area <create:pos1:pos2:delete:here:list>"
		);
		$this->setPermission("area.command");
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;
		if(!$sender->hasPermission('area.command')){
			$sender->sendMessage(TF::DARK_RED . 'You do not have permission to execute this command!');
			return;
		}
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}
		$name = $sender->getName();
		$x = $sender->getFloorX();
		$y = $sender->getFloorY();
		$z = $sender->getFloorZ();
		switch($args[0]){
			case "create":
				if(count($args) < 2){
					$sender->sendMessage(TF::RED . 'Usage: ' . TF::GRAY . '/area create <name>');
					return;
				}
				if(isset($this->main->areas[strtolower($args[1])])){
					$sender->sendMessage(TF::RED . "Failed to create area: Duplicate Name.");
					return;
				}
				if(!isset($this->main->pos1[$name]) || !isset($this->main->pos2[$name])){
					$sender->sendMessage(TF::RED . 'Please select both positions before creating the area.');
					return;
				}
				new Area(strtolower($args[1]), $this->main->pos1[$name], $this->main->pos2[$name], $sender->getLevel()->getName(), $this->main);
				$this->main->saveAreas();
				unset($this->main->pos1[$name], $this->main->pos2[$name]);
				$sender->sendMessage(TF::AQUA . "The area has successfully been created!");
				break;
			case "pos1":
				$this->main->pos1[$name] = $sender->asVector3()->floor();
				$sender->sendMessage(TF::AQUA . "First Position has been set to $x, $y, $z.");
				break;
			case "pos2":
				$this->main->pos2[$name] = $sender->asVector3()->floor();
				$sender->sendMessage(TF::AQUA . "Second Position has been set to $x, $y, $z");
				break;
			case "delete":
				if(count($args) < 2){
					$sender->sendMessage(TF::RED . 'Usage: ' . TF::GRAY . '/area delete <name>');
					return;
				}
				if(!isset($this->main->areas[strtolower($args[1])])){
					$sender->sendMessage(TF::RED . "That area does not exist.");
					return;
				}
				$area = $this->main->areas[strtolower($args[1])];
				if($area instanceof Area){
					$name = $area->getName();
					$area->delete();
				}
				$sender->sendMessage(TF::AQUA . "The area $name has been deleted.");
				break;
			case "list":
				$areas = null;
				foreach($this->main->areas as $area){
					$areas .= $area->getName() . ", ";
				}
				$count = count($this->main->areas);
				$sender->sendMessage(TF::AQUA . "Areas ($count): $areas");
				break;
			case "here":
				foreach($this->main->areas as $area){
					if($area->isIn($sender)){
						$sender->sendMessage(TF::AQUA . "You are currently in the area called {$area->getName()}.");
						return;
					}
				}
				$sender->sendMessage(TF::RED . "You are not in a protected area.");
				break;
			default:
				$sender->sendMessage($this->usageMessage);
		}

	}
}