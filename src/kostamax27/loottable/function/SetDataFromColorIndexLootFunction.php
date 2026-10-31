<?php

declare(strict_types=1);

namespace kostamax27\loottable\function;

use kostamax27\loottable\entity\EntityInspector;
use kostamax27\loottable\LootContext;
use pocketmine\block\utils\Colored;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;

final class SetDataFromColorIndexLootFunction implements LootFunction{

	public function __construct(
		readonly public EntityInspector $inspector
	){}

	public function apply(Item $item, LootContext $context) : Item{
		$color = $context->entity !== null ? $this->inspector->color($context->entity) : null;
		$block = $item instanceof ItemBlock ? $item->getBlock() : null;
		if($color === null || !$block instanceof Colored){
			return $item;
		}
		return $block->setColor($color)->asItem()->setNamedTag($item->getNamedTag())->setCount($item->getCount());
	}
}
