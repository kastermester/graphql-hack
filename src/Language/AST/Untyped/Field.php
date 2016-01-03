<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class Field extends Selection {
	private mixed $name;
	private mixed $alias;
	private mixed $arguments;
	private mixed $directives;
	private mixed $selectionSet;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $alias,
		mixed $arguments,
		mixed $directives,
		mixed $selectionSet
	){
		parent::__construct($loc);

		$this->name = $name;
		$this->alias = $alias;
		$this->arguments = $arguments;
		$this->directives = $directives;
		$this->selectionSet = $selectionSet;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function hasAlias() : bool {
		return !is_null($this->alias);
	}

	public function getAlias() : mixed {
		return $this->alias;
	}

	public function hasArguments() : bool {
		return !is_null($this->arguments);
	}

	public function getArguments() : mixed {
		return $this->arguments;
	}

	public function hasDirectives() : bool {
		return !is_null($this->directives);
	}

	public function getDirectives() : mixed {
		return $this->directives;
	}

	public function hasSelectionSet() : bool {
		return !is_null($this->selectionSet);
	}

	public function getSelectionSet() : mixed {
		return $this->selectionSet;
	}

	public function getKind() : string {
		return 'Field';
	}
}
