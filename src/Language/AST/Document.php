<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class Document extends Node {
	private ConstVector<Definition> $definitions;

	public function __construct(?Location $loc, ConstVector<Definition> $definitions){
		parent::__construct($loc);

		$this->definitions = $definitions;
	}

	public function getDefinitions() : ConstVector<Definition> {
		return $this->definitions;
	}
}
