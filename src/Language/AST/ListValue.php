<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class ListValue extends Value {
	private ConstVector<Value> $values;

	public function __construct(
		?Location $loc,
		ConstVector<Value> $values,
	){
		parent::__construct($loc);
		$this->values = $values;
	}

	public function getValues() : ConstVector<Value> {
		return $this->values;
	}
}
