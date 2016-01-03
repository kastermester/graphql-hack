<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class FragmentDefinition extends Definition {
	private ?NamedType $typeCondition;
	private ?ConstVector<Directive> $directives;
	private Name $name;
	private SelectionSet $selectionSet;

	public function __construct(
		?Location $loc,
		Name $name,
		SelectionSet $selectionSet,
		?NamedType $typeCondition,
		?ConstVector<Directive> $directives,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->selectionSet = $selectionSet;
		$this->typeCondition = $typeCondition;
		$this->directives = $directives;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getSelectionSet() : SelectionSet {
		return $this->selectionSet;
	}

	public function hasTypeCondition() : bool {
		return !is_null($this->typeCondition);
	}

	public function getTypeCondition() : ?NamedType {
		return $this->typeCondition;
	}

	public function hasDirectives() : bool {
		return !is_null($this->directives);
	}

	public function getDirectives() : ?ConstVector<Directive> {
		return $this->directives;
	}

	public function getKind() : string {
		return 'FragmentDefinition';
	}
}
