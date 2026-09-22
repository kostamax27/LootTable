<?php

declare(strict_types=1);

namespace kostamax27\loottable\condition;

use kostamax27\loottable\entity\EntityInspector;
use kostamax27\loottable\LootContext;

final class EntityVariantLootCondition implements LootCondition{

	public function __construct(
		readonly public EntityInspector $inspector,
		readonly public bool $mark,
		readonly public int $value
	){}

	public function test(LootContext $context) : bool{
		$entity = $context->entity;
		if($entity === null){
			return false;
		}
		return ($this->mark ? $this->inspector->markVariant($entity) : $this->inspector->variant($entity)) === $this->value;
	}
}
