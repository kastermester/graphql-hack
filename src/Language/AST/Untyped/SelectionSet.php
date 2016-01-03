<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class SelectionSet extends Node {
	private mixed $selections;

	public function __construct(
		?Location $loc,
		mixed $selections
	){
		parent::__construct($loc);
		$this->selections = $selections;
	}

	public function getSelections() : mixed {
		return $this->selections;
	}

	public function getKind() : string {
		return 'SelectionSet';
	}
}
