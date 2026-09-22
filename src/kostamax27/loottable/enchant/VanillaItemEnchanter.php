<?php

declare(strict_types=1);

namespace kostamax27\loottable\enchant;

use pocketmine\item\enchantment\AvailableEnchantmentRegistry;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use function array_values;

final class VanillaItemEnchanter implements ItemEnchanter{

	public function __construct(){
	}

	public function prepare(Item $item) : Item{
		if($item->getTypeId() !== ItemTypeIds::BOOK){
			return $item;
		}
		return VanillaItems::ENCHANTED_BOOK()->setNamedTag($item->getNamedTag())->setCount($item->getCount());
	}

	public function availableEnchantments(Item $item) : array{
		return array_values(AvailableEnchantmentRegistry::getInstance()->getAllEnchantmentsForItem($this->prepare($item)));
	}

	public function enchant(Item $item, EnchantmentInstance ...$enchantments) : Item{
		$item = $this->prepare($item);
		foreach($enchantments as $enchantment){
			$item->addEnchantment($enchantment);
		}
		return $item;
	}
}
