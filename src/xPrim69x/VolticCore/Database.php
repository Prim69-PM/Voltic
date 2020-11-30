<?php

namespace xPrim69x\VolticCore;
use pocketmine\Player;
use SQLite3;

class Database {

	private $main;
	private $facdb;
	private $db;

	public function __construct(Main $main){
		$this->main = $main;
		$this->db = new SQLite3($this->main->getDataFolder() . 'data.db');
		$this->facdb = new SQLite3($this->main->getDataFolder() . 'factions.db');
	}

	public function preparedb(){
		$this->db->exec("CREATE TABLE IF NOT EXISTS ranks (player TEXT PRIMARY KEY, rank TEXT)");
		$this->db->exec("CREATE TABLE IF NOT EXISTS stats (player TEXT PRIMARY KEY, kills INT, deaths INT, balance INT)");
		$this->facdb->exec("CREATE TABLE IF NOT EXISTS master (player TEXT PRIMARY KEY, faction TEXT, rank TEXT)");
	}

	public function registerPlayer(Player $player){
		$stats = $this->db->prepare("INSERT OR REPLACE INTO stats (player, kills, deaths, balance) VALUES (:player, :kills, :deaths, :balance)");
		$stats->bindValue(":player", $player->getUniqueId()->toString());
		$stats->bindValue(":kills", "0");
		$stats->bindValue(":deaths", "0");
		$stats->bindValue(":balance", "0");
		$rank = $this->db->prepare("INSERT OR REPLACE INTO ranks (player, rank) VALUES (:player, :rank)");
		$rank->bindValue(":player", $player->getUniqueId()->toString());
		$rank->bindValue(":rank", $this->main->getConfig()->get("default-rank"));
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
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO ranks (player, rank) VALUES (:player, :rank)");
		$stmt->bindValue(":player", $player->getUniqueId()->toString());
		$stmt->bindValue(":rank", $rank);
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

	public function createFaction(Player $player, string $name){
		$uuid = $player->getUniqueId()->toString();
		$stmt = $this->facdb->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
		$stmt->bindValue(":player", $uuid);
		$stmt->bindValue(":faction", $name);
		$stmt->bindValue(":rank", "Leader");
		$stmt->execute();
	}

	public function deleteFaction(Player $player){
		$faction = $this->getPlayerFaction($player);
		$this->facdb->query("DELETE FROM master WHERE faction='$faction';");
	}

	public function addPlayerToFaction(Player $player, string $faction){
		$uuid = $player->getUniqueId()->toString();
		$stmt = $this->facdb->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
		$stmt->bindValue(":player", $uuid);
		$stmt->bindValue(":faction", $faction);
		$stmt->bindValue(":rank", "Member");
		$stmt->execute();
	}

	public function removePlayerFromFaction(Player $player){
		$uuid = $player->getUniqueId()->toString();
		$this->facdb->query("DELETE FROM master WHERE player='$uuid';");
	}

	public function inFaction(Player $player) {
		$uuid = $player->getUniqueId()->toString();
		$result = $this->facdb->query("SELECT player FROM master WHERE player='$uuid';");
		$array = $result->fetchArray(1);
		return empty($array) == false;
	}

	public function getFaction(Player $player) {
		$uuid = $player->getUniqueId()->toString();
		$faction = $this->facdb->query("SELECT faction FROM master WHERE player='$uuid';");
		$factions = $faction->fetchArray(1);
		return $factions["faction"];
	}

	public function getLeader(string $faction) {
		$leader = $this->facdb->query("SELECT player FROM master WHERE faction='$faction' AND rank='Leader';");
		$leaders = $leader->fetchArray(1);
		return $leaders["player"];
	}

	public function getOfficers(string $faction) {
		$officer = $this->facdb->query("SELECT player FROM master WHERE faction='$faction' AND rank='Officer';");
		$officers = $officer->fetchArray(1);
		return $officers['player'];
	}

	public function getMembers(string $faction) { #NEED TO FIX THIS
		$member = $this->facdb->query("SELECT player FROM master WHERE faction='$faction'");
		$members = $member->fetchArray(1);
		return $members['player'];
	}

	public function setFacRank(Player $player, string $faction, string $rank){
		$uuid = $player->getUniqueId()->toString();
		$stmt = $this->facdb->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
		$stmt->bindValue(":player", $uuid);
		$stmt->bindValue(":faction", $faction);
		$stmt->bindValue(":rank", $rank);
		$stmt->execute();
	}

	public function getPlayerFaction(Player $player) {
		$uuid = $player->getUniqueId()->toString();
		$faction = $this->facdb->query("SELECT faction FROM master WHERE player='$uuid';");
		$factions = $faction->fetchArray(1);
		if(isset($factions["faction"])) return $factions["faction"];
		return "None";
	}

	public function getFactionRank(Player $player){
		$uuid = $player->getUniqueId()->toString();
		$faction = $this->facdb->query("SELECT rank FROM master WHERE player='$uuid';");
		$factions = $faction->fetchArray(1);
		return $factions["rank"];
	}

	public function isLeader(Player $player) {
		$uuid = $player->getUniqueId()->toString();
		$faction = $this->facdb->query("SELECT rank FROM master WHERE player='$uuid';");
		$factions = $faction->fetchArray(1);
		return $factions["rank"] == "Leader";
	}

	public function isOfficer(Player $player){
		$uuid = $player->getUniqueId()->toString();
		$faction = $this->facdb->query("SELECT rank FROM master WHERE player='$uuid';");
		$factions = $faction->fetchArray(1);
		return $factions["rank"] == "Officer";
	}

	public function isMember(Player $player) {
		$uuid = $player->getUniqueId()->toString();
		$faction = $this->facdb->query("SELECT rank FROM master WHERE player='$uuid';");
		$factionArray = $faction->fetchArray(1);
		return $factionArray["rank"] == "Member";
	}

	public function getNumberOfPlayers($faction) {
		$query = $this->facdb->query("SELECT COUNT(player) as count FROM master WHERE faction='$faction';");
		$number = $query->fetchArray();
		return $number['count'];
	}

	public function isFull(string $faction){
		return $this->getNumberOfPlayers($faction) >= $this->main->getConfig()->get("faction-max-members");
	}

	public function factionExists(string $faction) {
		$lower = strtolower($faction);
		$result = $this->facdb->query("SELECT player FROM master WHERE lower(faction)='$lower';");
		$array = $result->fetchArray(1);
		return empty($array) == false;
	}

}