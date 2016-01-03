<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class StringValue extends Value {
	private mixed $value;

	public function __construct(
		?Location $loc,
		mixed $value,
	){
		parent::__construct($loc);
		$this->value = $value;
	}

	public function getValue() : mixed {
		return $this->value;
	}

	public function getKind() : string {
		return 'StringValue';
	}
}
