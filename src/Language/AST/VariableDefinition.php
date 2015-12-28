<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

class VariableDefinition extends Definition {
	private Variable $variable;
	private TypeNode $type;
	private ?Value $defaultValue;

	public function __construct(
		?Location $loc,
		Variable $variable,
		TypeNode $type,
		?Value $defaultValue = null
	){
		parent::__construct($loc);

		$this->variable = $variable;
		$this->type = $type;
		$this->defaultValue = $defaultValue;
	}

	public function hasDefaultValue() : bool {
		return !is_null($this->defaultValue);
	}

	public function getDefaultValue() : ?Value {
		return $this->defaultValue;
	}

	public function getType() : TypeNode {
		return $this->type;
	}

	public function getVariable() : Variable {
		return $this->variable;
	}
}
