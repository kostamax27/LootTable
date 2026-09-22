# LootTable
Load Bedrock Edition loot tables and roll them in PocketMine-MP.

## Motive
Drop logic in plugins tends to be a `match` over entity classes, or a config list of `item: chance` pairs. Both hold up until the first request that does not fit the shape: "1 to 3 of this, only on hard, doubled by looting, with a 2.5% chance of an iron ingot instead". Each plugin then grows its own pools, weights, conditions and post-processing, none of them compatible with the others, and none of them editable by a server owner without a PHP change.

Bedrock already ships a format for exactly this problem. Every mob, block, chest, fishing and trading drop in the game is a JSON file under `loot_tables/` in the vanilla behaviour pack ([Mojang/bedrock-samples](https://github.com/Mojang/bedrock-samples/tree/main/behavior_pack/loot_tables)), the schema is [documented](https://learn.microsoft.com/en-us/minecraft/creator/documents/loottableoverview) and [well understood](https://wiki.bedrock.dev/loot/loot-tables), and add-on authors have been writing these files for years.

## Approach
[`LootTableFactory`](src/kostamax27/loottable/LootTableFactory.php) parses a file once into a [`LootTable`](src/kostamax27/loottable/LootTable.php). [`LootTable::generate()`](src/kostamax27/loottable/LootTable.php) rolls it against a [`LootContext`](src/kostamax27/loottable/LootContext.php) describing the situation and returns `list<Item>`.

```php
// onEnable
$factory = LootTableFactory::createDefault($this->getDataFolder() . "behavior_pack", EnchantmentLootingEvaluator::fromName("looting"));
$zombie_drops = $factory->fromFile($this->getDataFolder() . "behavior_pack/loot_tables/entities/zombie.json");

// EntityDeathEvent
$zombie = $event->getEntity();
$cause = $zombie->getLastDamageCause();
$killer = $cause instanceof EntityDamageByEntityEvent ? $cause->getDamager() : null;
$context = new LootContext(
	random: $random,
	entity: $zombie,
	killer: $killer,
	tool: $killer instanceof Player ? $killer->getInventory()->getItemInHand() : null,
	difficulty: LootDifficulty::fromWorld($zombie->getWorld())
);
$event->setDrops($zombie_drops->generate($context));
```

Everything the format describes is parsed into an object with a single job: [`WeightedLootPool`](src/kostamax27/loottable/pool/WeightedLootPool.php) and [`TieredLootPool`](src/kostamax27/loottable/pool/TieredLootPool.php) for the two pool kinds, [`WeightedLootEntry`](src/kostamax27/loottable/entry/WeightedLootEntry.php) around an `item`, `loot_table` or `empty` entry, one class per function and condition. Values are typed on the way in: [`LootData`](src/kostamax27/loottable/LootData.php) reads the JSON (`int()`, `intOr()`, `intNullable()` and so on per type) and throws with the full path on a mismatch, ranges are [`IntRange`](src/kostamax27/loottable/IntRange.php) or [`FloatRange`](src/kostamax27/loottable/FloatRange.php) depending on the field, difficulties and entity targets are enums. A tiered pool cannot hold weights or entry conditions, because Bedrock ignores them there.

```
Failed to parse loot table 'loot_tables/entities/zombie.json': 'pools[1].entries[0].functions[0] (set_count)': 'pools[1].entries[0].functions[0].count.max' directive not found
```

Where the server cannot answer a question on its own, the factory takes a strategy, following the same shape each time: an interface, a null or vanilla implementation, and a constructor argument.

| Question                                          | Strategy                                                                    | Default                                                                                                           |
|---------------------------------------------------|-----------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| what is this item name?                           | [`ItemResolver`](src/kostamax27/loottable/item/ItemResolver.php)            | [`ChainedItemResolver::createDefault()`](src/kostamax27/loottable/item/ChainedItemResolver.php)                   |
| what is the looting level?                        | [`LootingEvaluator`](src/kostamax27/loottable/looting/LootingEvaluator.php) | [`NullLootingEvaluator`](src/kostamax27/loottable/looting/NullLootingEvaluator.php), always 0                     |
| how does this item take enchantments?             | [`ItemEnchanter`](src/kostamax27/loottable/enchant/ItemEnchanter.php)       | [`VanillaItemEnchanter`](src/kostamax27/loottable/enchant/VanillaItemEnchanter.php), books become enchanted books |
| what type, variant and age is this entity?        | [`EntityInspector`](src/kostamax27/loottable/entity/EntityInspector.php)    | [`DefaultEntityInspector`](src/kostamax27/loottable/entity/DefaultEntityInspector.php)                            |
| where is the table named by a `loot_table` entry? | [`LootTableResolver`](src/kostamax27/loottable/LootTableResolver.php)       | [`DirectoryLootTableResolver`](src/kostamax27/loottable/DirectoryLootTableResolver.php) when a directory is given |

### Item names
`StringToItemParser` has never covered every block, so names go through a chain of resolvers, each answering `resolve(string $name, int $meta) : ?Item` or handing over to the next:

1. [`SavedItemDataItemResolver`](src/kostamax27/loottable/item/SavedItemDataItemResolver.php): the upgrader and deserializer pocketmine uses for world saves (`GlobalItemDataHandlers`). Covers every item the deserializer maps, every rename in BedrockItemUpgradeSchema (`minecraft:muttonRaw` → `minecraft:mutton`, `minecraft:dye` + 4 → `minecraft:lapis_lazuli`) and 1.12 block ids with a data value.
2. [`BlockRegistryItemResolver`](src/kostamax27/loottable/item/BlockRegistryItemResolver.php): current block ids. Every state in `RuntimeBlockStateRegistry` is run through the block serializer once and indexed by the id it produces; `BlockItemIdMap` reconciles ids that differ between block and item.
3. [`StringToItemParserItemResolver`](src/kostamax27/loottable/item/StringToItemParserItemResolver.php): aliases, including any a plugin registered under its own namespace.
4. [`LegacyStringToItemParserItemResolver`](src/kostamax27/loottable/item/LegacyStringToItemParserItemResolver.php): `wool:14`, `35:14`.

A name without a namespace is treated as vanilla, so `wool`, `minecraft:wool` and `muttonRaw` all resolve. Because the resolver understands data values, `set_data` and `random_aux_value` are real functions: the item is re-resolved under its own Bedrock id with the rolled value.

### Looting
Pocketmine ships no looting enchantment, so `looting_enchant` and `random_chance_with_looting` ask a `LootingEvaluator` instead of reading a level off the tool. [`EnchantmentLootingEvaluator::fromName("looting")`](src/kostamax27/loottable/looting/EnchantmentLootingEvaluator.php) picks up whatever enchantment a plugin registered under that name; [`ClosureLootingEvaluator`](src/kostamax27/loottable/looting/ClosureLootingEvaluator.php) takes anything else (a permission, a stat, a lore tag); [`StaticLootingEvaluator`](src/kostamax27/loottable/looting/StaticLootingEvaluator.php) fixes a level for tests. The evaluator is handed to functions at parse time, so it goes into the factory before any table is parsed.

### Enchanting
`enchant_with_levels` uses the server's own enchanting-table algorithm with the rolled level as the option's required XP level. `EnchantingHelper::createOption()` is private, so [`EnchantingHelperBridge`](src/kostamax27/loottable/enchant/EnchantingHelperBridge.php) reaches it by reflection, verifying the signature once and throwing a clear message if a pocketmine update changes it. `enchant_randomly` picks from `AvailableEnchantmentRegistry`; `specific_enchants` takes both the string and the `{id, level}` forms. All three go through the `ItemEnchanter`, which is what turns `minecraft:book` into an enchanted book while keeping its count, name and lore.

### Entities
`killed_by_entity`, `entity_killed`, `damaged_by_entity`, `has_variant`, `has_mark_variant` and `is_baby` read through an `EntityInspector`. The default reports the save id from `EntityFactory` when the class is registered and `getNetworkTypeId()` otherwise (custom mobs commonly borrow a vanilla network id, but save under their own), reads variants from the synced network metadata, and answers `is_baby` with `Ageable`. `damaged_by_entity` looks at the projectile when the last damage came through one.

### When a vanilla file asks for something pocketmine does not have
Functions with no server-side equivalent (`exploration_map`, `set_armor_trim`, ... see [`LootFunctionRegistry::UNSUPPORTED_VANILLA`](src/kostamax27/loottable/function/LootFunctionRegistry.php)) are registered as no-ops, and conditions the server cannot answer (`passenger_of_entity`, `bool_property`, `biome_has_tag`, `random_regional_difficulty_chance`, see [`LootConditionRegistry::UNSUPPORTED_VANILLA`](src/kostamax27/loottable/condition/LootConditionRegistry.php)) as always-false, so every file in the vanilla pack loads and the affected pools do the least surprising thing. Anything not in either list throws at parse time. Both are ordinary registrations and can be replaced.

## Examples

### 1. Block drops with a tool
```php
$context = new LootContext(
	random: $random,
	killer: $player,
	tool: $player->getInventory()->getItemInHand()
);
foreach($lapis_ore->generate($context) as $item){
	$world->dropItem($position, $item);
}
```

### 2. A condition of your own
Parsers receive the condition's JSON object as `LootData` and the factory. Validate through `LootData` so error messages keep the same shape as the built-in ones.

```php
$factory->condition_registry->register("myplugin:weather", static function(LootData $data, LootTableFactory $factory) : LootCondition{
	$raining = $data->bool("raining"); // "'pools[0].conditions[1].raining' must be a boolean, got string"
	return new ClosureLootCondition(static fn(LootContext $context) : bool => ($context->entity?->getWorld()->isRaining() ?? false) === $raining);
});
```

```json
{"condition": "myplugin:weather", "raining": true}
```

### 3. Custom items and mobs
Implement the strategy that knows about them and hand it to the constructor; keep the defaults for everything else.

```php
$factory = new LootTableFactory(
	LootFunctionRegistry::createDefault(),
	LootConditionRegistry::createDefault(),
	new ChainedItemResolver(new MyItemResolver(), ...ChainedItemResolver::createDefault()->resolvers),
	EnchantmentLootingEvaluator::fromName("looting"),
	new VanillaItemEnchanter(),
	new MyEntityInspector()
);
$factory->setTableResolver(new DirectoryLootTableResolver($directory, $factory));
```

### 4. Answering an unsupported condition
`biome_has_tag` is always-false by default. A server that knows its biomes replaces it:

```php
$factory->condition_registry->unregister("biome_has_tag");
$factory->condition_registry->register("biome_has_tag", static function(LootData $data, LootTableFactory $factory) : LootCondition{
	$tag = $data->string("tag");
	$negate = $data->stringOr("operator", "==") === "!=";
	return new ClosureLootCondition(static fn(LootContext $context) : bool => $negate !== BiomeTags::has($context->entity?->getPosition(), $tag));
});
```
