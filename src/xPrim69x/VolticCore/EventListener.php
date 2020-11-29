<?php

namespace xPrim69x\VolticCore;

use pocketmine\command\Command;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerChatEvent,
	PlayerCommandPreprocessEvent,
	PlayerDeathEvent,
	PlayerExhaustEvent,
	PlayerJoinEvent,
	PlayerLoginEvent,
	PlayerMoveEvent,
	PlayerQuitEvent};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\math\Vector3;
use xPrim69x\VolticCore\tasks\ScoreboardTask;

class EventListener implements Listener {

	private $main;

	public function __construct(Main $main){
		$this->main = $main;
	}

	public function onDamage(EntityDamageEvent $event){
		$player = $event->getEntity();
		switch($event->getCause()){
			case 4: //4 is fall damage
				$event->setCancelled();
				break;
		}
		foreach($this->main->areas as $area){
			if($area->isIn($player))
				$event->setCancelled();
		}
	}

	public function damageByEntity(EntityDamageByEntityEvent $event){
		$player = $event->getEntity();
		$attacker = $event->getDamager();
		if($player instanceof Player && $attacker instanceof Player){
			foreach([$player, $attacker] as $p){
				if(!$p instanceof Player) return;
				if(!$this->main->getUtils()->isTagged($p)){
					$p->sendMessage($this->main->getConfig()->get("combat-message"));
				}
				$this->main->getUtils()->setTagged($p);
			}
		}
	}

	public function onExhaust(PlayerExhaustEvent $event){
		$event->setCancelled();
	}

	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$event->setJoinMessage(TF::DARK_GREEN . '+' . TF::GREEN . " $name");
		if(!$this->main->getDBClass()->isRegistered($player))
			$this->main->getDBClass()->registerPlayer($player);
		$this->main->setPerms($player);
		$this->main->getScheduler()->scheduleRepeatingTask(new ScoreboardTask($this->main, $player), 40);
		Utils::addToArray($player);
	}

	public function onQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$event->setQuitMessage(TF::DARK_RED . '-' . TF::RED . " $name");
		if($this->main->getUtils()->isTagged($player)) $player->kill();
		Utils::removeFromArray($player);
	}

	public function onLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$player->teleport($this->main->getServer()->getDefaultLevel()->getSafeSpawn());
	}

	public function onChat(PlayerChatEvent $event){
		if($event->isCancelled()) return;
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		$chat = new Chat($this->main);
		$format = $chat->getFormat($player, $msg);
		$event->setFormat($format);
	}

	public function preProcess(PlayerCommandPreProcessEvent $event){
		$msg = $event->getMessage();
		if(isset($msg[1])){
			if($msg[0] === "/" && $msg[1] === " ") $event->setCancelled();
		}
		$player = $event->getPlayer();
		if($this->main->getUtils()->isTagged($player)){
			if($msg[0] === "/"){
				$args = array_map("stripslashes", str_getcsv(substr($msg, 1), " "));
				$label = "";
				$target = $this->main->getServer()->getCommandMap()->matchCommand($label, $args);
				if($target instanceof Command && in_array(strtolower($label), $this->main->getConfig()->get("combat-commands"))){
					$event->setCancelled();
					$player->sendMessage($this->main->getConfig()->get("command-message"));
				}
			}
		}
	}

	public function onMove(PlayerMoveEvent $event){
		$player = $event->getPlayer();
		$block = $player->getLevel()->getBlock($player->getSide(0));
		if($block->getId() === 165) {
			if(Utils::hasCooldown($player, 1)){
				$cd = Utils::getCooldown($player, 1);
				$message = $this->main->getConfig()->get("slimecooldown-message");
				$message = str_replace("{slimecooldown}", $cd, $message);
				$player->sendPopup($message);
			} else {
				$player->setMotion(new Vector3(0, mt_rand(25, 40) / 10, 0));
				Utils::addCooldown($player, 1);
			}
		}
		#if($block->getId() === 152)
			#$player->knockBack($player, 0, $player->x, $player->z, 0.8);
	}

	public function onDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		if($this->main->getUtils()->isTagged($player)) $this->main->getUtils()->setTagged($player, false);
		if($player instanceof Player){
			$this->main->getDBClass()->addDeaths($player, 1);
			$name = $player->getName();
			$cause = $player->getLastDamageCause();
			if($cause instanceof EntityDamageByEntityEvent){
				$damager = $cause->getDamager();
				if(!$damager instanceof Player) return;
				$this->main->getDBClass()->addKills($damager, 1);
				$dname = $damager->getName();
				$hp = round($damager->getHealth(), 1);
				$msgs = $this->main->getConfig()->get('death-messages');
				$msg = $msgs[array_rand($msgs)];
				$format = $this->main->getConfig()->get('format');
				$format = str_replace(['{player}', '{msg}', '{killer}', '{hp}'], [$name, $msg, $dname, $hp], $format);
				$event->setDeathMessage($format);
			}
		}
	}

	public function onDataPacketReveive(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		$utils = $this->main->getUtils();
		if ($packet instanceof InventoryTransactionPacket) {
			$transactionType = $packet->transactionType;
			if ($transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
				$utils->addClick($player);
				$clicks = $utils->getClicks($player);
				$player->sendTip("§9CPS: §f$clicks");
			}
		}
		if ($packet instanceof LevelSoundEventPacket) {
			$sound = $packet->sound;
			if ($sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) {
				$utils->addClick($player);
				$clicks = $utils->getClicks($player);
				$player->sendTip("§9CPS: §f$clicks");
			}
		}
		if($packet instanceof EmotePacket){
			$emote = $packet->getEmoteId();
			Server::getInstance()->broadcastPacket($player->getViewers(), EmotePacket::create($player->getId(), $emote, 1 << 0));
		}
	}

}