<?php

namespace xPrim69x\VolticCore\kits;

use InvalidArgumentException;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Player;
use xPrim69x\VolticCore\Main;

class Kit {

	private $main;
	private $data;
	private $name;
	private $formname;
	private $price;
	private $cd;
	private $cds = [];
	private $items = [];
	private $armor = ['helmet' => null, 'chestplate' => null, 'leggings' => null, 'boots' => null];
	private $slots = [];
	private $effects = [];

	public function __construct(Main $main, string $name, array $data){
		$this->main = $main;
		$this->name = $name;
		$this->data = $data;
		$this->cd = $this->getMinutes();

		if(isset($data['price']) && $data['price'] !== 0){
			$this->price = (int) $data['price'];
		}

		if(file_exists($main->getDataFolder() . 'cooldowns/' . strtolower($name) . '.yml')){
			$this->cds = unserialize(file_get_contents($main->getDataFolder() . 'cooldowns/' . strtolower($name) . '.yml'), ['allowed_classes' => false]);
		}

		foreach($data['items'] as $items){
			$item = $this->loadItem($items);
			if($item !== null){
				$this->items[] = $item;
			}
		}

		isset($data['helmet']) && ($this->armor['helmet'] = $this->loadItem($data['helmet']));
		isset($data['chestplate']) && ($this->armor['chestplate'] = $this->loadItem($data['chestplate']));
		isset($data['leggings']) && ($this->armor['leggings'] = $this->loadItem($data['leggings']));
		isset($data['boots']) && ($this->armor['boots'] = $this->loadItem($data['boots']));

		if(isset($data['slots']) && is_array($data['slots'])){
			foreach($data['slots'] as $index => $itemstring){
				$item = $this->loadItem($itemstring);
				if($item !== null){
					$this->slots[$index] = $item;
				}
			}
		}

		if(isset($data['effects']) && is_array($data['effects'])){
			foreach($data['effects'] as $effectstring){
				$effect = $this->loadEffect($effectstring);
				if($effect !== null){
					$this->effects[] = $effect;
				}
			}
		}

		if(isset($data['form-name'])){
			$this->formname = $data['form-name'];
		}
	}

	public function getName(){
		return $this->name;
	}

	public function getFormName(){
		return $this->formname;
	}

	public function confirm(Player $player){
		foreach($this->items as $item){
			$player->getInventory()->addItem($item);
		}

		$this->armor['helmet'] !== null && $player->getArmorInventory()->setHelmet($this->armor['helmet']);
		$this->armor['chestplate'] !== null && $player->getArmorInventory()->setChestplate($this->armor['chestplate']);
		$this->armor['leggings'] !== null && $player->getArmorInventory()->setLeggings($this->armor['leggings']);
		$this->armor['boots'] !== null && $player->getArmorInventory()->setBoots($this->armor['boots']);

		foreach($this->slots as $slot => $item){
			$player->getInventory()->setItem($slot, $item);
		}

		foreach($this->effects as $effect){
			$player->addEffect(clone $effect);
		}

		if($this->cd){
			$this->cds[$player->getName()] = $this->cd;
		}

	}

	public function loadItem(string $item) : ?Item{
		$array = explode(':', $item);
		if(count($array) < 2){
			$this->main->getLogger()->warning("Item $item for the kit {$this->name} could not be added because the item was formatted incorrectly.");
			return null;
		}
		$name = array_shift($array);
		$meta = array_shift($array);

		try{
			$item2 = Item::fromString($name . ':' . $meta);
		}catch(InvalidArgumentException $exception){
			$this->main->getLogger()->warning("Item $item for the kit {$this->name} could not be added because of something not configured properly.");
			$this->main->getLogger()->warning($exception->getMessage());
			return null;
		}

		if(!empty($array)){
			$amount = array_shift($array);
			if(is_numeric($amount)){
				$item2->setCount((int)$amount);
			} else {
				$this->main->getLogger()->warning("Item $item for the kit {$this->name} could not be added because the amount is not a number.");
				return null;
			}
		}

		if(!empty($array)){
			$name = array_shift($array);
			if(strtolower($name) !== 'default'){
				$item2->setCustomName($name);
			}
		}

		if(!empty($array)){
			$enchantarray = array_chunk($array, 2);
			foreach($enchantarray as $data){
				if(count($data) !== 2) $this->main->getLogger()->warning("Enchantment {$data[0]} for the kit {$this->name} could not be added on $item because the enchantment was not formatted correctly.");
				$enchant = Enchantment::getEnchantmentByName($data[0]);
				if($enchant === null) $this->main->getLogger()->warning("Enchantment {$data[0]} for the kit {$this->name} could not be added on $item because the enchantment does not exist.");
				if(!is_numeric($array[1])) $this->main->getLogger()->warning("Enchantment {$data[0]} for the kit {$this->name} could not be added on $item because the enchantment level is not a number.");
				$item2->addEnchantment(new EnchantmentInstance($enchant, (int) $data[1]));
			}
		}
		return $item2;
	}

	public function loadEffect(string $effectstring) : ?EffectInstance{
		$array = explode(':', $effectstring);
		if(count($array) < 2){
			$this->main->getLogger()->warning("Effect $effectstring for the kit {$this->name} could not be added because the name and level weren't specified.");
			return null;
		}
		$name = array_shift($array);
		$duration = array_shift($array);
		if(!is_numeric($duration)){
			$this->main->getLogger()->warning("Effect $effectstring for the kit {$this->name} could not be added because the duration is not a number.");
			return null;
		}
		if(!empty($array)){
			$amplifier = array_shift($array);
			if(!is_numeric($amplifier)){
				$this->main->getLogger()->warning("Effect $effectstring for the kit {$this->name} could not be added because the amplifier is not a number.");
				return null;
			}
		} else {
			$amplifier = 0;
		}
		$effect = Effect::getEffectByName($name);
		if($effect === null){
			$this->main->getLogger()->warning("Effect $effectstring for the kit {$this->name} could not be added because the effect does not exist.");
			return null;
		}
		return new EffectInstance($effect, (int) $duration * 20, (int) $amplifier);
	}

	public function handle(Player $player){
		if(!$this->hasKitPerms($player)){
			$player->sendMessage(TF::RED . "You do not have permission to use this kit!");
			return false;
		}
		if(isset($this->cds[$player->getName()])){
			$player->sendMessage(TF::RED . "You are on cooldown for this kit! You will be able to claim it in " . TF::WHITE . $this->getRemainingCD($player) . '.');
			return false;
		}

		if($price = $this->price){
			if($this->main->getDBClass()->hasMoney($player, $price)){
				$this->main->getDBClass()->removeMoney($player, $price);
				$this->confirm($player);
				$player->sendMessage(TF::GREEN . "You have bought the kit {$this->name} for $$price.");
				return true;
			}
			$balance = $this->main->getDBClass()->getMoney($player);
			$player->sendMessage(TF::RED . "You cannot afford this kit. The price is $" . $this->price . " and your balance is $$balance.");
		} else {
			$this->confirm($player);
			$player->sendMessage(TF::GREEN . "You have selected the kit {$this->name}.");
			return true;
		}
		return false;
	}

	public function hasKitPerms(Player $player) : bool{
		if(!isset($this->data['permission'])) return true;
		if($player->hasPermission($this->data['permission'])) return true;
		return false;
	}

	public function getMinutes() : int {
		$min = 0;
		if(isset($this->data['cooldown']['minutes'])){
			$min += (int) $this->data['cooldown']['minutes'];
		}
		if(isset($this->data['cooldown']['hours'])){
			$min += (int) $this->data['cooldown']['hours'] * 60;
		}
		return $min;
	}

	public function getRemainingCD(Player $player) : string{
		$minutes = $this->cds[$player->getName()];
		if($minutes < 60){
			if($minutes === 1) return "$minutes minute";
			return "$minutes minutes";
		}
		$mod = $minutes % 60;
		$hours = floor($minutes / 60);
		if($mod !== 0){
			return "$hours hours and $mod minutes";
		}
		return "$hours hours";
	}

	public function processCD(){
		foreach($this->cds as $player => $min){
			--$this->cds[$player];
			if($this->cds[$player] <= 0){
				unset($this->cds[$player]);
			}
		}
	}

	public function save() : void{
		if(!empty($this->cds)){
			file_put_contents($this->main->getDataFolder() . 'cooldowns/' . strtolower($this->name) . '.yml', serialize($this->cds));
		}
	}


}