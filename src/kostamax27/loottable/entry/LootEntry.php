<?php

declare(strict_types=1);

namespace kostamax27\loottable\entry;

use kostamax27\loottable\LootContext;
use pocketmine\item\Item;

interface LootEntry{

	/**
	 * @return list<Item>
	 */
	public function generate(LootContext $context) : array;
}
