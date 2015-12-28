<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class ObjectValue extends Value {
	private ConstVector<ObjectField> $fields;

	public function __construct(
		?Location $loc,
		ConstVector<ObjectField> $fields,
	){
		parent::__construct($loc);
		$this->fields = $fields;
	}

	public function getFields() : ConstVector<ObjectField> {
		return $this->fields;
	}
}
