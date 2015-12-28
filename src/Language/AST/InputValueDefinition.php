<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

class InputValueDefinition extends TypeDefinition {
	private Name $name;
	private TypeNode $type;
	private ?Value $defaultValue;

	public function __construct(
		?Location $loc,
		Name $name,
		TypeNode $type,
		?Value $defaultValue,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->type = $type;
		$this->defaultValue = $defaultValue;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getType() : TypeNode {
		return $this->type;
	}

	public function hasDefaultValue() : bool {
		return !is_null($this->defaultValue);
	}

	public function getDefaultValue() : ?Value {
		return $this->defaultValue;
	}
}
