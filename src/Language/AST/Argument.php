<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

class Argument extends Node {
	private Name $name;
	private Value $value;

	public function __construct(
		?Location $loc,
		Name $name,
		Value $value
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->value = $value;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getValue() : Value {
		return $this->value;
	}
}
