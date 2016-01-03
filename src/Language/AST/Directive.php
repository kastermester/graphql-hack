<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class Directive extends Node {
	private Name $name;
	private ?ConstVector<Argument> $arguments;

	public function __construct(
		?Location $loc,
		Name $name,
		?ConstVector<Argument> $arguments,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->arguments = $arguments;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function hasArguments() : bool {
		return !is_null($this->arguments);
	}

	public function getArguments() : ?ConstVector<Argument> {
		return $this->arguments;
	}

	public function getKind() : string {
		return 'Directive';
	}
}
