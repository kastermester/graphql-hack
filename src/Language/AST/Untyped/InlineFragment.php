<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class InlineFragment extends Selection {
	private mixed $selectionSet;
	private mixed $typeCondition;
	private mixed $directives;

	public function __construct(
		?Location $loc,
		mixed $selectionSet,
		mixed $typeCondition,
		mixed $directives,
	){
		parent::__construct($loc);
		$this->selectionSet = $selectionSet;
		$this->typeCondition = $typeCondition;
		$this->directives = $directives;
	}

	public function getSelectionSet() : mixed {
		return $this->selectionSet;
	}

	public function hasTypeCondition() : bool {
		return !is_null($this->typeCondition);
	}

	public function getTypeCondition() : mixed {
		return $this->typeCondition;
	}

	public function hasDirectives() : bool {
		return !is_null($this->directives);
	}

	public function getDirectives() : mixed {
		return $this->directives;
	}

	public function getKind() : string {
		return 'InlineFragment';
	}
}
