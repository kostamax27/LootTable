<?php

declare(strict_types=1);

namespace kostamax27\loottable\function;

use InvalidArgumentException;
use kostamax27\loottable\enchant\EnchantingHelperBridge;
use kostamax27\loottable\enchant\ItemEnchanter;
use kostamax27\loottable\IntRange;
use kostamax27\loottable\LootContext;
use pocketmine\item\Item;
use function count;

final class EnchantWithLevelsLootFunction implements LootFunction{

	public function __construct(
		readonly public ItemEnchanter $enchanter,
		readonly public IntRange $levels,
		readonly public bool $treasure = false
	){
		$this->levels->min >= 0 || throw new InvalidArgumentException("'levels' must be >= 0, got {$this->levels->min}");
	}

	public function apply(Item $item, LootContext $context) : Item{
		$enchantments = EnchantingHelperBridge::roll($context->random, $this->enchanter->prepare($item), $this->levels->sample($context->random));
		return count($enchantments) === 0 ? $item : $this->enchanter->enchant($item, ...$enchantments);
	}
}
