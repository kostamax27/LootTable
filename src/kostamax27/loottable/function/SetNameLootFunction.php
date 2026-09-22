<?php

declare(strict_types=1);

namespace kostamax27\loottable\function;

use kostamax27\loottable\LootContext;
use pocketmine\item\Item;

final class SetNameLootFunction implements LootFunction{

	public function __construct(
		readonly public string $name
	){}

	public function apply(Item $item, LootContext $context) : Item{
		$item->setCustomName($this->name);
		return $item;
	}
}
