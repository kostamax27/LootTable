<?php

declare(strict_types=1);

namespace kostamax27\loottable\entry;

use kostamax27\loottable\LootContext;
use kostamax27\loottable\pool\LootPool;
use function array_push;

/**
 * An entry followed by pools that roll whenever it is picked, even if it yields nothing.
 */
final class NestedPoolsLootEntry implements LootEntry{

	/**
	 * @param list<LootPool> $pools
	 */
	public function __construct(
		readonly public LootEntry $entry,
		readonly public array $pools
	){}

	public function generate(LootContext $context) : array{
		$result = $this->entry->generate($context);
		foreach($this->pools as $pool){
			array_push($result, ...$pool->generate($context));
		}
		return $result;
	}
}
