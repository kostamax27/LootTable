<?php

declare(strict_types=1);

namespace kostamax27\loottable\entry;

use kostamax27\loottable\function\LootFunction;
use kostamax27\loottable\LootContext;
use kostamax27\loottable\LootTableResolver;
use function count;

final class LootTableLootEntry implements LootEntry{

	/**
	 * @param list<LootFunction> $functions
	 */
	public function __construct(
		readonly public string $name,
		readonly private LootTableResolver $resolver,
		readonly public array $functions
	){}

	public function generate(LootContext $context) : array{
		$items = $this->resolver->resolve($this->name)->generate($context);
		if(count($this->functions) === 0){
			return $items;
		}
		foreach($items as $index => $item){
			foreach($this->functions as $function){
				$item = $function->apply($item, $context);
			}
			$items[$index] = $item;
		}
		return $items;
	}
}
