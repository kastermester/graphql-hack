<?hh // strict

namespace GraphQL;

abstract final class Assert {
	public static function isNonNull<T>(?T $val) : T {
		invariant(!is_null($val), "Value was expected to be non null");
		return $val;
	}

	public static function isInstanceOf<T, Tu super T>(Tu $val, classname<T> $class) : T {
		invariant($val instanceof $class, "Expected value to be instance of $class");
		return $val;
	}

	public static function isInt<T>(T $val) : int {
		invariant(is_int($val), 'Expected value to be an int');
		return $val;
	}

	public static function isString<T>(T $val) : string {
		invariant(is_string($val), 'Expected value to be a string');
		return $val;
	}
}
