<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class UnionTypeDefinition extends TypeDefinition {
	private Name $name;
	private ConstVector<NamedType> $types;

	public function __construct(
		?Location $loc,
		Name $name,
		ConstVector<NamedType> $types,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->types = $types;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getTypes() : ConstVector<NamedType> {
		return $this->types;
	}
}
