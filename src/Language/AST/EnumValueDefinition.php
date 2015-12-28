<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

class EnumValueDefinition extends Node {
	private Name $name;

	public function __construct(
		?Location $loc,
		Name $name,
	){
		parent::__construct($loc);
		$this->name = $name;
	}

	public function getName() : Name {
		return $this->name;
	}
}
