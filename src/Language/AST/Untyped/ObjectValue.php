<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class ObjectValue extends Value {
	private mixed $fields;

	public function __construct(
		?Location $loc,
		mixed $fields,
	){
		parent::__construct($loc);
		$this->fields = $fields;
	}

	public function getFields() : mixed {
		return $this->fields;
	}

	public function getKind() : string {
		return 'ObjectValue';
	}
}
