<?php
namespace FixItem\KienMC;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\item\StringToItemParser;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\{VanillaItems, ItemBlock, Item, Tool, Armor};
use pocketmine\event\Listener;
use pocketmine\plugin\{Plugin, PluginBase};
use pocketmine\command\{Command, CommandSender, CommandExecutor};
use FixItem\KienMC\FormAPI\{Form, FormAPI, ModalForm, CustomForm, SimpleForm};
use pocketmine\console\ConsoleCommandSender;
use DaPigGuy\libPiggyEconomy\libPiggyEconomy;

class Main extends PluginBase implements Listener{
	
	public function onEnable(): void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		libPiggyEconomy::init();
        $this->economyProvider = libPiggyEconomy::getProvider($this->getConfig()->get("economy"));
	}
	
	public $economyProvider;
	
	public function getEconomyProvider(){
		return $this->economyProvider;
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool{
		switch($cmd->getName()){
			case "fixitem":
			if($sender instanceof Player){
				$this->menu($sender);
			}else{
				$sender->sendMessage("Use Command In Game");
			}
			break;
			case "fix":
			if($sender instanceof Player){
				if($sender->hasPermission("fix.cmd")){
					$this->fixItemHand($sender);
				}else{
					$sender->sendMessage("You Don't Have Permission To Use This Command");
				}
			}else{
				$sender->sendMessage("Use Command In Game");
			}
			break;
			case "fixall":
			if($sender instanceof Player){
				if($sender->hasPermission("fixall.cmd")){
					$this->fixItemAll($sender);
				}else{
					$sender->sendMessage("You Don't Have Permission To Use This Command");
				}
			}else{
				$sender->sendMessage("Use Command In Game");
			}
			break;
		}
		return true;
	}
	
	public function fixItemHand($sender){
		$item=$sender->getInventory()->getItemInHand();
		if(!$item instanceof Armor && !$item instanceof Tool) return;
		$item->setDamage(0);
		$sender->getInventory()->setItemInHand($item);
		return;
	}
	
	public function fixItemAll($sender){
		$inv=$sender->getInventory();
		$contents=$inv->getContents();
		foreach($contents as $slot => $item){
			if($item instanceof Armor || $item instanceof Tool){
				if($item->getDamage()!==0){
					$item->setDamage(0);
					$inv->setItem($slot, $item);
				}
			}
		}
		$arInv=$sender->getArmorInventory();
		for($slot=0;$slot<4;$slot++){
			$item=$arInv->getItem($slot);
			if($item instanceof Armor || $item instanceof Tool){
				if($item->getDamage()!==0){
					$item->setDamage(0);
					$arInv->setItem($slot, $item);
				}
			}
		}
		return;
	}
	
	public function menu($sender){
		$form=new SimpleForm(function(Player $sender, $data){
			if($data==null) return;
			switch($data){
				case 0:
				break;
				case 1:
				$this->getEconomyProvider()->getMoney($sender, function(int|float $money) use($sender){
					$price=$this->getConfig()->get("price");
					if($money<$price){
						$sender->sendMessage("§l§c• You Dont Have Enough Money To Fix");
						return;
					}
					$item=$sender->getInventory()->getItemInHand();
					if(!$item instanceof Item){
						$sender->sendMessage("§l§c• You Dont Have Item In Hand");
						return;
					}
					if($item instanceof ItemBlock){
						$sender->sendMessage("§l§c• Your Item In Hand Is Not Tool Or Armor");
						return;
					}
					if($item->getNamedTag()->getShort("Damage")===0){
						$sender->sendMessage("§l§c• Your Item Is Not Damaged");
						return;
					}
					$this->getEconomyProvider()->takeMoney($sender, (int)($price));
					$this->fixItemHand($sender);
					$sender->sendMessage("§l§c•§a Fix Item Successfully");
				});
				break;
				case 2:
				$this->getEconomyProvider()->getMoney($sender, function(int|float $money) use($sender){
					$count=0;
					$inv=$sender->getInventory();
					$contents=$inv->getContents();
					foreach($contents as $slot => $item){
						if($item instanceof Armor || $item instanceof Tool){
							if($item->getDamage()!==0){
								$count++;
							}
						}
					}
					$arInv=$sender->getArmorInventory();
					for($slot=0;$slot<4;$slot++){
						$item=$arInv->getItem($slot);
						if($item instanceof Armor || $item instanceof Tool){
							if($item->getDamage()!==0){
								$count++;
							}
						}
					}
					if($count==0){
						$sender->sendMessage("§l§c• You Dont Have Any Item Damaged");
						return;
					}
					$price=$this->getConfig()->get("price");
					$totalprice=(int)($price)*(int)($count);
					if($money<$totalprice){
						$sender->sendMessage("§l§c• You Dont Have Enough Money To Fix All Item, You Need§e ".$totalprice." Money§c To Fix");
						return;
					}
					$this->getEconomyProvider()->takeMoney($sender, (int)($totalprice));
					$this->fixItemAll($sender);
					$sender->sendMessage("§l§c•§a You Fixed§e ".$count." Item §aIn Your Inventory With Price§e ".$totalprice." Money§a Successfully");
				});
				break;
			}
		});
		$form->setTitle("§l§c♦§e Fix Item §c♦");
		$this->getEconomyProvider()->getMoney($sender, function(int|float $money) use($form){
			$price=$this->getConfig()->get("price");
			$form->setContent("§l§c•§a Your Money:§e ".$money." Money\n§l§c•§a Price To Fix Item:§e ".$price." Money");
		});
		$form->addButton("§l§c•§9 Exit §c•");
		$form->addButton("§l§c•§9 Fix Item In Hand §c•");
		$form->addButton("§l§c•§9 Fix All Item §c•");
		$form->sendToPlayer($sender);
	}
}
