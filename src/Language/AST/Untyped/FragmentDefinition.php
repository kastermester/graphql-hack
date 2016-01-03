<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class FragmentDefinition extends Definition {
	private mixed $name;
	private mixed $selectionSet;
	private mixed $typeCondition;
	private mixed $directives;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $selectionSet,
		mixed $typeCondition,
		mixed $directives,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->selectionSet = $selectionSet;
		$this->typeCondition = $typeCondition;
		$this->directives = $directives;
	}

	public function getName() : mixed {
		return $this->name;
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
		return 'FragmentDefinition';
	}
}
