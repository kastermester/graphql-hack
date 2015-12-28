<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

class EnumValue extends Value {
	private string $value;

	public function __construct(
		?Location $loc,
		string $value,
	){
		parent::__construct($loc);
		$this->value = $value;
	}

	public function getValue() : string {
		return $this->value;
	}
}
