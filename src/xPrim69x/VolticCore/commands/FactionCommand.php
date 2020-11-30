<?php

namespace xPrim69x\VolticCore\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use xPrim69x\VolticCore\Main;
use xPrim69x\VolticCore\Utils;

class FactionCommand extends Command{

	private $main;

	const CREATEU = TF::RED . "Usage: " . TF::GRAY . "/f create <name>";
	const KICKU = TF::RED . "Usage: " . TF::GRAY . "/f kick <player>";
	const INVITEU = TF::RED . "Usage: " . TF::GRAY . "/f invite <player>";
	const PROMOTEU = TF::RED . "Usage: " . TF::GRAY . "/f promote <player>";
	const DEMOTEU = TF::RED . "Usage: " . TF::GRAY . "/f demote <player>";
	const LEADERU = TF::RED . "Usage: " . TF::GRAY . "/f leader <player>";

	public function __construct(Main $main){
		parent::__construct(
			"faction",
			TF::AQUA . "Manage factions | /f help",
			TF::RED . "Usage: " . TF::GRAY . "/f help"
		);
		$this->setAliases(['f', 'clan']);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}
		$db = $this->main->getDBClass();
		$mg = $this->main->getFactionManager();
		switch($args[0]){
			case "help":
				$sender->sendMessage(TF::GREEN . "Faction Commands:\n" .
					" - /f create <name>\n" .
					" - /f disband\n" .
					" - /f kick <player>\n" .
					" - /f join\n" .
					" - /f leave\n" .
					" - /f invite <player>\n" .
					" - /f promote <player>\n" .
					" - /f demote <player>\n" .
					" - /f leader <player>\n" .
					" - To use Faction chat simply put a * before your message."
				);
				break;
			case "create":
				if(!isset($args[1])){
					$sender->sendMessage(self::CREATEU);
					return;
				}
				$name = $args[1];
				if($db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are already in a faction!");
					return;
				}
				if(!Utils::validName($name)){
					$sender->sendMessage(TF::RED .  "Enter a valid name!");
					return;
				}
				if($db->factionExists($name)){
					$sender->sendMessage(TF::RED . "That faction already exists!");
					return;
				}
				$fc = $this->main->getConfig()->get("faction-max-length");
				if(strlen($name) > (int) $fc){
					$sender->sendMessage(TF::RED . "That name is too long! Faction names must be under $fc characters.");
					return;
				}
				$db->createFaction($sender, $name);
				$sender->sendMessage(TF::GREEN . "You have created the faction " . TF::WHITE . "$name!" . TF::GREEN . " Do /f help for a list of faction commands.");
				break;
			case "disband":
				if(!$db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are not in a faction!");
					return;
				}
				if(!$db->isLeader($sender)){
					$sender->sendMessage(TF::RED . "You are not the faction leader!");
					return;
				}
				if(!isset($args[1])){
					$sender->sendMessage(TF::GREEN . "Type /f disband confirm to disband your faction!");
					return;
				}
				if($args[1] !== "confirm"){
					$sender->sendMessage(TF::GREEN . "Type /f disband confirm to disband your faction!");
					return;
				}
				$db->deleteFaction($sender);
				$sender->sendMessage(TF::GREEN . "You have successfully disbanded your faction!");
				break;
			case "join":
				if($db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are already in a faction!");
					return;
				}
				if(!$mg->hasInvite($sender)){
					$sender->sendMessage(TF::RED . "You do not have any invites!");
					return;
				}
				$fac = $mg->getFactionByInvite($sender);
				if(!$db->factionExists($fac)){
					$sender->sendMessage(TF::RED . "That faction no longer exists!");
					return;
				}
				if($db->isFull($fac)){
					$sender->sendMessage(TF::RED . "That faction is now full!");
					return;
				}
				$db->addPlayerToFaction($sender, $fac);
				$mg->removeInvite($sender);
				$sender->sendMessage(TF::GREEN . "You have joined the faction " . TF::WHITE . "$fac!");
				break;
			case "invite":
				if(!isset($args[1])){
					$sender->sendMessage(self::INVITEU);
					return;
				}
				if(!$db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are not in a faction!");
					return;
				}
				if($db->isMember($sender)){
					$sender->sendMessage(TF::RED . "Only Officers and the faction leader can invite!");
					return;
				}
				$fac = $db->getPlayerFaction($sender);
				if($db->isFull($fac)){
					$sender->sendMessage(TF::RED . "Your faction is full!");
					return;
				}
				$player = Server::getInstance()->getPlayer($args[1]);
				if($player === null){
					$sender->sendMessage(TF::RED . "That player is not online!");
					return;
				}
				if($sender->getName() === $player->getName()){
					$sender->sendMessage(TF::RED . "You cannot invite yourself!");
					return;
				}
				if($db->inFaction($player)){
					$sender->sendMessage(TF::RED . "That player is already in a faction!");
					return;
				}
				$mg->addInvite($player, $fac);
				$sender->sendMessage(TF::GREEN . "You have invited " . TF::WHITE . $player->getName() . TF::GREEN . " to your faction!");
				$player->sendMessage(TF::WHITE . $sender->getName() . TF::GREEN . " has invited you to the faction " . TF::WHITE . "$fac!");
				break;
			case "leave":
				if(!$db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are not in a faction!");
					return;
				}
				if($db->isLeader($sender)){
					$sender->sendMessage(TF::RED . "You must make someone else leader before leaving!");
					return;
				}
				$db->removePlayerFromFaction($sender);
				$sender->sendMessage(TF::GREEN . "You have successfully left the faction!");
				break;
			case "kick":
				if(!isset($args[1])){
					$sender->sendMessage(self::KICKU);
					return;
				}
				if(!$db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are not in a faction!");
					return;
				}
				if($db->isMember($sender)){
					$sender->sendMessage(TF::RED . "Only officers and the faction leader can kick people!");
					return;
				}
				$player = Server::getInstance()->getPlayer($args[1]);
				if($player === null){
					$sender->sendMessage(TF::RED . "That player is not online!");
					return;
				}
				if($sender->getName() === $player->getName()){
					$sender->sendMessage(TF::RED . "You cannot kick yourself!");
					return;
				}
				if($db->getFaction($sender) !== $db->getFaction($player)){
					$sender->sendMessage(TF::RED . "That player is not in your faction!");
					return;
				}
				$db->removePlayerFromFaction($player);
				$sender->sendMessage(TF::GREEN . "You have successfully kicked " . TF::WHITE . $player->getName() . TF::GREEN . " from your faction!");
				break;
			case "promote":
				if(!isset($args[1])){
					$sender->sendMessage(self::PROMOTEU);
					return;
				}
				if(!$db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are not in a faction!");
					return;
				}
				if($db->isMember($sender)){
					$sender->sendMessage(TF::RED . "Only officers and the faction leader can promote people!");
					return;
				}
				$player = Server::getInstance()->getPlayer($args[1]);
				if($player === null){
					$sender->sendMessage(TF::RED . "That player is not online!");
					return;
				}
				if($sender->getName() === $player->getName()){
					$sender->sendMessage(TF::RED . "You cannot promote yourself!");
					return;
				}
				if($db->getFaction($sender) !== $db->getFaction($player)){
					$sender->sendMessage(TF::RED . "That player is not in your faction!");
					return;
				}
				if($db->isOfficer($player) || $db->isLeader($player)){
					$sender->sendMessage(TF::RED . "Officers and leaders can not be promoted!");
					return;
				}
				$db->setFacRank($player, $db->getFaction($player), "Officer");
				$sender->sendMessage(TF::GREEN . "You have promoted " . TF::WHITE . $player->getName() . TF::GREEN . " to Officer!");
				$player->sendMessage(TF::WHITE . $sender->getName() . TF::GREEN . " has promoted you to Officer!");
				break;
			case "demote":
				if(!isset($args[1])){
					$sender->sendMessage(self::DEMOTEU);
					return;
				}
				if(!$db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are not in a faction!");
					return;
				}
				if(!$db->isLeader($sender)){
					$sender->sendMessage(TF::RED . "Only the faction leader can demote people!");
					return;
				}
				$player = Server::getInstance()->getPlayer($args[1]);
				if($player === null){
					$sender->sendMessage(TF::RED . "That player is not online!");
					return;
				}
				if($sender->getName() === $player->getName()){
					$sender->sendMessage(TF::RED . "You cannot demote yourself!");
					return;
				}
				if($db->getFaction($sender) !== $db->getFaction($player)){
					$sender->sendMessage(TF::RED . "That player is not in your faction!");
					return;
				}
				if($db->isLeader($player) || $db->isMember($player)){
					$sender->sendMessage(TF::RED . "The player cannot be demoted!");
					return;
				}
				$db->setFacRank($player, $db->getFaction($player), "Member");
				$sender->sendMessage(TF::GREEN . "You have demoted " . TF::WHITE . $player->getName() . TF::GREEN . " to Member!");
				$player->sendMessage(TF::RED . "You have been demoted by " . TF::WHITE . "{$sender->getName()}.");
				break;
			case "leader":
				if(!isset($args[1])){
					$sender->sendMessage(self::LEADERU);
					return;
				}
				if(!$db->inFaction($sender)){
					$sender->sendMessage(TF::RED . "You are not in a faction!");
					return;
				}
				if(!$db->isLeader($sender)){
					$sender->sendMessage(TF::RED . "Only the faction leader can use this command!");
					return;
				}
				if(!isset($args[2])){
					$sender->sendMessage(TF::GREEN . "Type /f leader <player> confirm to set that player to leader." . TF::RED . " WARNING: You will be demoted to member if you run this command.");
					return;
				}
				if($args[2] !== "confirm"){
					$sender->sendMessage(TF::GREEN . "Type /f leader <player> confirm to set that player to leader." . TF::RED . " WARNING: You will be demoted to member if you run this command.");
					return;
				}
				$player = Server::getInstance()->getPlayer($args[1]);
				if($player === null){
					$sender->sendMessage(TF::RED . "That player is not online!");
					return;
				}
				if($sender->getName() === $player->getName()){
					$sender->sendMessage(TF::RED . "You cannot set yourself to Leader!");
					return;
				}
				if($db->getFaction($sender) !== $db->getFaction($player)){
					$sender->sendMessage(TF::RED . "That player is not in your faction!");
					return;
				}
				$db->setFacRank($player, $db->getFaction($player), "Leader");
				$db->setFacRank($sender, $db->getFaction($sender), "Member");
				$sender->sendMessage(TF::GREEN . "You have promoted " . TF::WHITE . $player->getName() . TF::GREEN . " to Leader!");
				$sender->sendMessage(TF::GREEN . "You're faction rank is now Member.");
				$player->sendMessage(TF::WHITE . $sender->getName() . TF::GREEN . " has promoted you to Leader!");
				break;
			default:
				$sender->sendMessage($this->usageMessage);
		}
	}

}