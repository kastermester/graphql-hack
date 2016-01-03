<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\AST\Node;
use GraphQL\Language\Location;

class VariableDefinition extends Definition {
	private mixed $variable;
	private mixed $type;
	private mixed $defaultValue;

	public function __construct(
		?Location $loc,
		mixed $variable,
		mixed $type,
		mixed $defaultValue = null
	){
		parent::__construct($loc);

		$this->variable = $variable;
		$this->type = $type;
		$this->defaultValue = $defaultValue;
	}

	public function hasDefaultValue() : bool {
		return !is_null($this->defaultValue);
	}

	public function getDefaultValue() : mixed {
		return $this->defaultValue;
	}

	public function getType() : mixed {
		return $this->type;
	}

	public function getVariable() : mixed {
		return $this->variable;
	}

	public function getKind() : string {
		return 'VariableDefinition';
	}
}
