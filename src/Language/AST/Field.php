<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class Field extends Selection {
	private Name $name;
	private ?Name $alias;
	private ?ConstVector<Argument> $arguments;
	private ?ConstVector<Directive> $directives;
	private ?SelectionSet $selectionSet;

	public function __construct(
		?Location $loc,
		Name $name,
		?Name $alias,
		?ConstVector<Argument> $arguments,
		?ConstVector<Directive> $directives,
		?SelectionSet $selectionSet
	){
		parent::__construct($loc);

		$this->name = $name;
		$this->alias = $alias;
		$this->arguments = $arguments;
		$this->directives = $directives;
		$this->selectionSet = $selectionSet;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function hasAlias() : bool {
		return !is_null($this->alias);
	}

	public function getAlias() : ?Name {
		return $this->alias;
	}

	public function hasArguments() : bool {
		return !is_null($this->arguments);
	}

	public function getArguments() : ?ConstVector<Argument> {
		return $this->arguments;
	}

	public function hasDirectives() : bool {
		return !is_null($this->directives);
	}

	public function getDirectives() : ?ConstVector<Directive> {
		return $this->directives;
	}

	public function hasSelectionSet() : bool {
		return !is_null($this->selectionSet);
	}

	public function getSelectionSet() : ?SelectionSet {
		return $this->selectionSet;
	}

	public function getKind() : string {
		return 'Field';
	}
}
