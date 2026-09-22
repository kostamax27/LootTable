<?php

declare(strict_types=1);

namespace kostamax27\loottable\function;

use kostamax27\loottable\LootContext;
use pocketmine\item\Item;

final class NoopLootFunction implements LootFunction{

	private static self $instance;

	public static function instance() : self{
		return self::$instance ??= new self();
	}

	private function __construct(){
	}

	public function apply(Item $item, LootContext $context) : Item{
		return $item;
	}
}
