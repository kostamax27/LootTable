<?php

declare(strict_types=1);

namespace kostamax27\loottable\condition;

use kostamax27\loottable\LootContext;

final class NeverLootCondition implements LootCondition{

	private static self $instance;

	public static function instance() : self{
		return self::$instance ??= new self();
	}

	private function __construct(){
	}

	public function test(LootContext $context) : bool{
		return false;
	}
}
