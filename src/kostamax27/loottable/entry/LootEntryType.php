<?php

declare(strict_types=1);

namespace kostamax27\loottable\entry;

enum LootEntryType : string{

	case ITEM = "item";
	case LOOT_TABLE = "loot_table";
	case EMPTY = "empty";
}
