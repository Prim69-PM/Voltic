<?php

namespace xPrim69x\VolticCore\tasks;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use xPrim69x\VolticCore\utils\Scoreboards;
use xPrim69x\VolticCore\Main;

class ScoreboardTask extends Task {

	private $player;
	private $main;

	public function __construct(Main $main, Player $player){
		$this->main = $main;
		$this->player = $player;
	}

	public function onRun(int $currentTick) : void {
		$this->sb($this->player);
		if(!$this->player->isOnline()) {
			$this->main->getScheduler()->cancelTask($this->getTaskId());
		}
	}

	public function sb(Player $player){
		$sb = Scoreboards::getInstance();
		$pc = Main::getInstance();
		$kills = $pc->getDBClass()->getKills($player);
		$deaths = $pc->getDBClass()->getDeaths($player);
		$kdr = $pc->getDBClass()->getKDR($player);
		$money = $pc->getDBClass()->getMoney($player);
		$ping = $player->getPing();
		$on = count($this->main->getServer()->getOnlinePlayers());
		$rank = $pc->getDBClass()->getRank($player);
		$facrank = $pc->getDBClass()->getFacRank($player);

		$lines = [
			1 => "§9K: §f$kills §9D: §f$deaths",
			2 => "§9KDR: §f$kdr",
			3 => "§9Ping: §f$ping",
			4 => "§9Online: §f$on",
			5 => "--------------",
			6 => "§9Rank: §f$rank",
			7 => "§9Faction: §fTODO",
			8 => "§9Faction Rank: §f$facrank",
			9 => "§9Money: §f$$money"
		];

		$sb->new($player, "ObjectiveName", "§l§9Vasar");
		foreach($lines as $line => $content)
			$sb->setLine($player, $line, $content);
	}
}