<?hh // strict

namespace GraphQL;

abstract final class Assert {
	public static function isNonNull<T>(?T $val) : T {
		if (is_null($val)) {
			throw new AssertException("Value was expected to be non null");
		}
		return $val;
	}

	public static function isInstanceOf<T>(mixed $val, classname<T> $class) : T {
		if (!($val instanceof $class)){
			throw new AssertException("Expected value to be instance of $class");
		}

		return $val;
	}
}
