<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class EnumTypeDefinition extends TypeDefinition {
	private mixed $name;
	private mixed $values;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $values,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->values = $values;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function getValues() : mixed {
		return $this->values;
	}

	public function getKind() : string {
		return 'EnumTypeDefinition';
	}
}
