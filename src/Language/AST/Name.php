<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

class Name extends Node {
	private string $value;

	public function __construct(?Location $loc, string $value) {
		parent::__construct($loc);

		$this->value = $value;
	}

	public function getValue() : string {
		return $this->value;
	}

	public function getKind() : string {
		return 'Name';
	}
}
