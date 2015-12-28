<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

class BooleanValue extends Value {
	private bool $value;

	public function __construct(
		?Location $loc,
		bool $value,
	){
		parent::__construct($loc);
		$this->value = $value;
	}

	public function getValue() : bool {
		return $this->value;
	}
}
