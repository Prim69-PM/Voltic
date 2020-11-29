<?php

namespace xPrim69x\VolticCore;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use xPrim69x\VolticCore\commands\{AddMoneyCommand,
	AddRankCommand,
	AreaCommand,
	BalanceCommand,
	DelRankCommand,
	KitCommand,
	PayCommand,
	PlayerVaultCommand,
	RankListCommand,
	RemoveMoneyCommand,
	SetMoneyCommand,
	SetRankCommand};
use xPrim69x\VolticCore\kits\Kit;
use xPrim69x\VolticCore\kits\KitManager;
use xPrim69x\VolticCore\tasks\ClearEntitiesTask;
use xPrim69x\VolticCore\tasks\CooldownTask;
use xPrim69x\VolticCore\utils\Scoreboards;

class Main extends PluginBase{

	public $cooldown = [1 => [], 2 => [], 3 => []];
	public $clicks = [];
	public $areas = [];
	public $pos1 = [];
	public $pos2 = [];

	private $kitmanager;
	private $dbclass;
	private $utils;
	private $chat;

	public static $instance;

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		foreach(['ranks.json', 'kits.yml', 'config.yml'] as $file){
			$this->saveResource($file);
		}
		$this->registerCommands();
		$this->unregisterCommands($this->getConfig()->get('commands-unregistered'));
		$this->preparefiles();
		$this->saverank();
		$this->init();
		$this->getScheduler()->scheduleRepeatingTask(new ClearEntitiesTask($this), $this->getConfig()->get("clear-entities-interval") * 20);
		$this->getScheduler()->scheduleDelayedRepeatingTask(new CooldownTask($this->getKitManager()), 1200, 1200);
	}

	public function onDisable(){
		foreach($this->getKitManager()->kits as $kit){
			if($kit instanceof Kit) $kit->save();
		}
		$this->getKitManager()->kits = [];
	}

	public function registerCommands(){
		$this->getServer()->getCommandMap()->registerAll($this->getName(), [
			new AreaCommand($this),
			new PlayerVaultCommand($this),
			new PayCommand($this),
			new BalanceCommand($this),
			new SetMoneyCommand($this),
			new AddMoneyCommand($this),
			new RemoveMoneyCommand($this),
			new SetRankCommand($this),
			new AddRankCommand($this),
			new DelRankCommand($this),
			new RankListCommand($this),
			new KitCommand($this)
		]);
	}

	private function unregisterCommands(array $commands) : void{
		$commandMap = $this->getServer()->getInstance()->getCommandMap();
		foreach($commandMap->getCommands() as $cmd){
			if(in_array($cmd->getName(), $commands)){
				$cmd->setLabel("disabled_" . $cmd->getName());
				$commandMap->unregister($cmd);
			}
		}
	}

	public function init(){
		$this->chat = new Chat($this);
		$this->kitmanager = new KitManager($this);
		$this->dbclass = new Database($this);
		$this->dbclass->preparedb();
		$this->utils = new Utils();
		Scoreboards::$instance = new Scoreboards($this);
		self::$instance = $this;
		$areas = json_decode(file_get_contents($this->getDataFolder() . "areas.json"), true);
		foreach($areas as $area){
			new Area($area['name'], new Vector3($area['pos1'][0], $area['pos1'][1], $area['pos1'][2]), new Vector3($area['pos2'][0], $area['pos2'][1], $area['pos2'][2]), $area["level"], $this);
		}
		if(!InvMenuHandler::isRegistered())
			InvMenuHandler::register($this);
		$this->getKitManager()->loadKits();
	}

	public function preparefiles(){
		if(!file_exists($this->getDataFolder() . "areas.json")){
			file_put_contents($this->getDataFolder() . "areas.json", "[]");
		}
		if(!is_dir($this->getDataFolder() . "cooldowns/")){
			mkdir($this->getDataFolder() . "cooldowns/");
		}
	}

	public function saverank(){
		$def = $this->getConfig()->get("default-rank");
		$ranks = json_decode(file_get_contents($this->getDataFolder() . "ranks.json"), true);
		if(!isset($ranks[$def])){
			$ranks[$def] = [
				"inherits" => [],
				"permissions" => [],
				"nametag" => "§7[$def] §f{name}",
				"chat" => "§7[$def] §f{name}: {msg}"
			];
			file_put_contents($this->getDataFolder() . "ranks.json", json_encode($ranks, JSON_PRETTY_PRINT));
			$this->getLogger()->warning("Default rank was not found in the ranks file. Creating it.");
		}
	}

	public static function getInstance() : Main{
		return self::$instance;
	}

	public function saveAreas(){
		$areas = [];
		foreach($this->areas as $area){
			$areas[] = [
				"name" => $area->getName(),
				"pos1" => [$area->getPos1()->getX(), $area->getPos1()->getY(), $area->getPos1()->getZ()],
				"pos2" => [$area->getPos2()->getX(), $area->getPos2()->getY(), $area->getPos2()->getZ()],
				"level" => $area->getLevel()->getName()
			];
		}
		file_put_contents($this->getDataFolder() . "areas.json", json_encode($areas, 128));
	}

	public function setPerms(Player $player){
		$rank = $this->getDBClass()->getRank($player);
		$r2 = json_decode(file_get_contents($this->getDataFolder() . "ranks.json"), true);
		$perms = $r2[$rank]["permissions"];
		foreach($perms as $perm){
			$player->addAttachment($this, $perm, true);
		}
	}

	public function changedRank(Player $player, string $oldrank, string $newrank){
		$dr = json_decode(file_get_contents($this->getDataFolder() . "ranks.json"), true);
		$oldperms = $dr[$oldrank]["permissions"];
		$newperms = $dr[$newrank]["permissions"];
		foreach($oldperms as $oldperm){
			$player->addAttachment($this, $oldperm, false);
		}
		foreach($newperms as $perm){
			$player->addAttachment($this, $perm, true);
		}
	}

	public function getKitManager() : KitManager{
		return $this->kitmanager;
	}

	public function getUtils() : Utils{
		return $this->utils;
	}

	public function getChat() : Chat{
		return $this->chat;
	}

	public function getDBClass() : Database {
		return $this->dbclass;
	}

}
