<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class InlineFragment extends Selection {
	private ?NamedType $typeCondition;
	private ?ConstVector<Directive> $directives;
	private SelectionSet $selectionSet;

	public function __construct(
		?Location $loc,
		SelectionSet $selectionSet,
		?NamedType $typeCondition,
		?ConstVector<Directive> $directives,
	){
		parent::__construct($loc);
		$this->selectionSet = $selectionSet;
		$this->typeCondition = $typeCondition;
		$this->directives = $directives;
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
}
