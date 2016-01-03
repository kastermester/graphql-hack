<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class Directive extends Node {
	private mixed $name;
	private mixed $arguments;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $arguments,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->arguments = $arguments;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function hasArguments() : bool {
		return !is_null($this->arguments);
	}

	public function getArguments() : mixed {
		return $this->arguments;
	}

	public function getKind() : string {
		return 'Directive';
	}
}
