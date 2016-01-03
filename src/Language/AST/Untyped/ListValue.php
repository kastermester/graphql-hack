<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class ListValue extends Value {
	private mixed $values;

	public function __construct(
		?Location $loc,
		mixed $values,
	){
		parent::__construct($loc);
		$this->values = $values;
	}

	public function getValues() : mixed {
		return $this->values;
	}

	public function getKind() : string {
		return 'ListValue';
	}
}
