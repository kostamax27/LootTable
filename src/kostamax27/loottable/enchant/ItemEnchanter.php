<?php

declare(strict_types=1);

namespace kostamax27\loottable\enchant;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

interface ItemEnchanter{

	/**
	 * Returns the item that will carry the enchantments (an enchanted
	 * book for a book), or $item itself when no conversion applies.
	 * Count and named tag must survive a conversion.
	 */
	public function prepare(Item $item) : Item;

	/**
	 * Returns every enchantment that may be applied to the result of
	 * {@see self::prepare()}, primary or secondary.
	 *
	 * @return list<Enchantment>
	 */
	public function availableEnchantments(Item $item) : array;

	/**
	 * @return Item result of {@see self::prepare()} carrying $enchantments
	 */
	public function enchant(Item $item, EnchantmentInstance ...$enchantments) : Item;
}
