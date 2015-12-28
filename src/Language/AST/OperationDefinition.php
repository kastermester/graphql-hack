<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

enum Operation : string {
	QUERY = 'query';
	MUTATION = 'mutation';
	SUBSCRIPTION = 'subscription';
}

class OperationDefinition extends Definition {
	private Operation $operation;

	private ?Name $name;
	private ?ConstVector<VariableDefinition> $variableDefinitions;
	private ?ConstVector<Directive> $directives;
	private SelectionSet $selectionSet;

	public function __construct(
		?Location $loc,
		Operation $operation,
		SelectionSet $selectionSet,
		?ConstVector<VariableDefinition> $variableDefinitions,
		?ConstVector<Directive> $directives
	){
		parent::__construct($loc);
		$this->operation = $operation;
		$this->selectionSet = $selectionSet;
		$this->variableDefinitions = $variableDefinitions;
		$this->directives = $directives;
	}

	public function hasName() : bool {
		return !is_null($this->name);
	}

	public function getName() : ?Name {
		return $this->name;
	}

	public function hasVariableDefinitions() : bool {
		return !is_null($this->variableDefinitions);
	}

	public function getVariableDefinitions() : ?ConstVector<VariableDefinition> {
		return $this->variableDefinitions;
	}

	public function hasDirectives() : bool {
		return !is_null($this->directives);
	}

	public function getDirectives() : ?ConstVector<Directive> {
		return $this->directives;
	}

	public function getSelectionSet() : SelectionSet {
		return $this->selectionSet;
	}
}
