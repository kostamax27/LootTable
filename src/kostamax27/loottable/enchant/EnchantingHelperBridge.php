<?php

declare(strict_types=1);

namespace kostamax27\loottable\enchant;

use Closure;
use pocketmine\item\enchantment\EnchantingHelper;
use pocketmine\item\enchantment\EnchantingOption;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\utils\Random;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use function count;

final class EnchantingHelperBridge{

	private const METHOD = "createOption"; // private in pocketmine :(

	/** @var Closure(Random, Item, int) : EnchantingOption */
	private static Closure $create_option;

	private function __construct(){
	}

	/**
	 * @param Item $item the item a table would be enchanting (an enchanted book, not a book)
	 * @return list<EnchantmentInstance>
	 */
	public static function roll(Random $random, Item $item, int $required_xp_level) : array{
		return (self::$create_option ??= self::bind())($random, $item, $required_xp_level)->getEnchantments();
	}

	/**
	 * @return Closure(Random, Item, int) : EnchantingOption
	 */
	private static function bind() : Closure{
		try{
			$method = new ReflectionMethod(EnchantingHelper::class, self::METHOD);
		}catch(ReflectionException $e){
			throw new RuntimeException(EnchantingHelper::class . "::" . self::METHOD . "() no longer exists, update " . self::class . " for this pocketmine version", $e->getCode(), $e);
		}
		$method->isStatic() || throw new RuntimeException(EnchantingHelper::class . "::" . self::METHOD . "() is expected to be static");
		$parameters = $method->getParameters();
		count($parameters) === 3 || throw new RuntimeException(EnchantingHelper::class . "::" . self::METHOD . "() is expected to take 3 parameters, got " . count($parameters));
		foreach([Random::class, Item::class, "int"] as $index => $expected){
			$type = $parameters[$index]->getType();
			$type instanceof ReflectionNamedType && $type->getName() === $expected || throw new RuntimeException(EnchantingHelper::class . "::" . self::METHOD . "() parameter {$index} is expected to be {$expected}, got " . ($type?->__toString() ?? "untyped"));
		}
		$return = $method->getReturnType();
		$return instanceof ReflectionNamedType && $return->getName() === EnchantingOption::class || throw new RuntimeException(EnchantingHelper::class . "::" . self::METHOD . "() is expected to return " . EnchantingOption::class . ", got " . ($return?->__toString() ?? "untyped"));
		return $method->getClosure(null);
	}
}
