<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class Document extends Node {
	private mixed $definitions;

	public function __construct(?Location $loc, mixed $definitions){
		parent::__construct($loc);

		$this->definitions = $definitions;
	}

	public function getDefinitions() : mixed {
		return $this->definitions;
	}

	public function getKind() : string {
		return 'Document';
	}
}
