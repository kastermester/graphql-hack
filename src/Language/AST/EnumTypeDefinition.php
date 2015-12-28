<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class EnumTypeDefinition extends TypeDefinition {
	private Name $name;
	private ConstVector<EnumValueDefinition> $values;

	public function __construct(
		?Location $loc,
		Name $name,
		ConstVector<EnumValueDefinition> $values,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->values = $values;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getValues() : ConstVector<EnumValueDefinition> {
		return $this->values;
	}
}
