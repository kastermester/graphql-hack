<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class Argument extends Node {
	private mixed $name;
	private mixed $value;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $value
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->value = $value;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function getValue() : mixed {
		return $this->value;
	}

	public function getKind() : string {
		return 'Argument';
	}
}
