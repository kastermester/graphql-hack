<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class InterfaceTypeDefinition extends TypeDefinition {
	private mixed $name;
	private mixed $fields;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $fields,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->fields = $fields;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function getFields() : mixed {
		return $this->fields;
	}

	public function getKind() : string {
		return 'InterfaceTypeDefinition';
	}
}
