<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class OperationDefinition extends Definition {
	private mixed $operation;

	private mixed $name;
	private mixed $variableDefinitions;
	private mixed $directives;
	private mixed $selectionSet;

	public function __construct(
		?Location $loc,
		mixed $operation,
		mixed $selectionSet,
		mixed $name,
		mixed $variableDefinitions,
		mixed $directives
	){
		parent::__construct($loc);
		$this->operation = $operation;
		$this->selectionSet = $selectionSet;
		$this->variableDefinitions = $variableDefinitions;
		$this->name = $name;
		$this->directives = $directives;
	}

	public function hasName() : bool {
		return !is_null($this->name);
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function hasVariableDefinitions() : bool {
		return !is_null($this->variableDefinitions);
	}

	public function getVariableDefinitions() : mixed {
		return $this->variableDefinitions;
	}

	public function hasDirectives() : bool {
		return !is_null($this->directives);
	}

	public function getDirectives() : mixed {
		return $this->directives;
	}

	public function getSelectionSet() : mixed {
		return $this->selectionSet;
	}

	public function getOperation() : mixed {
		return $this->operation;
	}

	public function getKind() : string {
		return 'OperationDefinition';
	}
}
