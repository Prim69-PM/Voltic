<?php

namespace xPrim69x\VolticCore;
use pocketmine\Player;
use SQLite3;

class Database {

	private $main;
	private $db;

	public function __construct(Main $main){
		$this->main = $main;
		$this->db = new SQLite3($this->main->getDataFolder() . 'data.db');
	}

	public function preparedb(){
		$this->db->exec("CREATE TABLE IF NOT EXISTS vaults (player TEXT PRIMARY KEY, number INT, data TEXT)");
		$this->db->exec("CREATE TABLE IF NOT EXISTS ranks (player TEXT PRIMARY KEY, rank TEXT, facrank TEXT)");
		$this->db->exec("CREATE TABLE IF NOT EXISTS stats (player TEXT PRIMARY KEY, kills INT, deaths INT, balance INT)");
	}

	public function save(Player $player, int $vaultnumber, string $vaultdata){
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO vaults (player, number, data) VALUES (:player, :number, :data)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":number", $vaultnumber);
		$stmt->bindValue(":data", $vaultdata);
		$stmt->execute();
	}

	public function getVault(Player $player, int $vaultnumber){
		$uuid = $player->getUniqueId()->toString();
		$res = $this->db->query("SELECT data FROM vaults WHERE player='$uuid' AND number ='$vaultnumber'");
		$ar = $res->fetchArray(1);
		return (string) $ar["data"];
	}

	public function registerPlayer(Player $player){
		$stats = $this->db->prepare("INSERT OR REPLACE INTO stats (player, kills, deaths, balance) VALUES (:player, :kills, :deaths, :balance)");
		$stats->bindValue(":player", $player->getUniqueId()->toString());
		$stats->bindValue(":kills", "0");
		$stats->bindValue(":deaths", "0");
		$stats->bindValue(":balance", "0");
		$rank = $this->db->prepare("INSERT OR REPLACE INTO ranks (player, rank, facrank) VALUES (:player, :rank, :facrank)");
		$rank->bindValue(":player", $player->getUniqueId()->toString());
		$rank->bindValue(":rank", $this->main->getConfig()->get("default-rank"));
		$rank->bindValue(":facrank", null);
		$stats->execute();
		$rank->execute();
	}

	public function isRegistered(Player $player) : bool{
		$uuid = $player->getUniqueId()->toString();
		$res = $this->db->query("SELECT player FROM stats WHERE player='$uuid';");
		$ar = $res->fetchArray(1);
		return empty($ar) == false;
	}

	public function getKills(Player $player){
		$uuid = $player->getUniqueId()->toString();
		$res = $this->db->query("SELECT kills FROM stats WHERE player='$uuid'");
		$ar = $res->fetchArray(1);
		return (int) $ar["kills"];
	}

	public function getDeaths(Player $player){
		$uuid = $player->getUniqueId()->toString();
		$res = $this->db->query("SELECT deaths FROM stats WHERE player='$uuid'");
		$ar = $res->fetchArray(1);
		return (int) $ar["deaths"];
	}

	public function getKDR(Player $player){
		$kills = $this->getKills($player);
		$deaths = $this->getDeaths($player);
		$deaths !== 0 ? $kdr = $kills / $deaths : $kdr = 0;
		if($kdr !== 0) $kdr = number_format($kdr, 1);
		return $kdr;
	}

	public function getMoney(Player $player){
		$uuid = $player->getUniqueId()->toString();
		$res = $this->db->query("SELECT balance FROM stats WHERE player='$uuid'");
		$ar = $res->fetchArray(1);
		return (int) $ar["balance"];
	}

	public function addKills(Player $player, int $kills = 1){
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO stats (player, kills, deaths, balance) VALUES (:player, :kills, :deaths, :balance)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":kills", $this->getKills($player) + $kills);
		$stmt->bindValue(":deaths", $this->getDeaths($player));
		$stmt->bindValue(":balance", $this->getMoney($player));
		$stmt->execute();
	}

	public function addDeaths(Player $player, int $deaths = 1){
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO stats (player, kills, deaths, balance) VALUES (:player, :kills, :deaths, :balance)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":kills", $this->getKills($player));
		$stmt->bindValue(":deaths", $this->getDeaths($player) + $deaths);
		$stmt->bindValue(":balance", $this->getMoney($player));
		$stmt->execute();
	}

	public function addMoney(Player $player, int $money){
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO stats (player, kills, deaths, balance) VALUES (:player, :kills, :deaths, :balance)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":kills", $this->getKills($player));
		$stmt->bindValue(":deaths", $this->getDeaths($player));
		$stmt->bindValue(":balance", $this->getMoney($player) + abs($money));
		$stmt->execute();
	}

	public function removeMoney(Player $player, int $money){
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO stats (player, kills, deaths, balance) VALUES (:player, :kills, :deaths, :balance)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":kills", $this->getKills($player));
		$stmt->bindValue(":deaths", $this->getDeaths($player));
		$stmt->bindValue(":balance", $this->getMoney($player) - abs($money));
		$stmt->execute();
	}

	public function setMoney(Player $player, int $money){
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO stats (player, kills, deaths, balance) VALUES (:player, :kills, :deaths, :balance)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":kills", $this->getKills($player));
		$stmt->bindValue(":deaths", $this->getDeaths($player));
		$stmt->bindValue(":balance", $money);
		$stmt->execute();
	}

	public function hasMoney(Player $player, int $amount){
		if($this->getMoney($player) >= $amount) return true;
		return false;
	}

	public function setRank(Player $player, string $rank){
		$oldrank = $this->getRank($player);
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO ranks (player, rank, facrank) VALUES (:player, :rank, :facrank)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":rank", $rank);
		$stmt->bindValue(":facrank", $this->getFacRank($player));
		$stmt->execute();
		$newrank = $this->getRank($player);
		$this->main->changedRank($player, $oldrank, $newrank);
	}

	public function getRank(Player $player){
		$uuid = $player->getUniqueId()->toString();
		$res = $this->db->query("SELECT rank FROM ranks WHERE player='$uuid'");
		$ar = $res->fetchArray(1);
		$ranks = json_decode(file_get_contents($this->main->getDataFolder() . "ranks.json"), true);
		if(!isset($ranks[$ar["rank"]])){
			$this->setRank($player, $this->main->getConfig()->get("default-rank"));
		}
		return (string) $ar["rank"];
	}

	public function setFacRank(Player $player, string $facrank){
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO ranks (player, rank, facrank) VALUES (:player, :rank, :facrank)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":rank", $this->getRank($player));
		$stmt->bindValue(":facrank", $facrank);
		$stmt->execute();
	}

	public function getFacRank(Player $player){
		$uuid = $player->getUniqueId()->toString();
		$res = $this->db->query("SELECT facrank FROM ranks WHERE player='$uuid'");
		$ar = $res->fetchArray(1);
		return (string) $ar["facrank"];
	}

}