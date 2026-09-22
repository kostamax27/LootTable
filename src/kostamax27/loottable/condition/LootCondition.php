<?php

declare(strict_types=1);

namespace kostamax27\loottable\condition;

use kostamax27\loottable\LootContext;

interface LootCondition{

	public function test(LootContext $context) : bool;
}
