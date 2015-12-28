<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class SelectionSet extends Node {
	private ConstVector<Selection> $selections;

	public function __construct(
		?Location $loc,
		ConstVector<Selection> $selections
	){
		parent::__construct($loc);
		$this->selections = $selections;
	}

	public function getSelections() : ConstVector<Selection> {
		return $this->selections;
	}
}
