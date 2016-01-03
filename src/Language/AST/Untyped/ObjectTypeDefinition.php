<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class ObjectTypeDefinition extends TypeDefinition {
	private mixed $name;
	private mixed $fields;
	private mixed $interfaces;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $fields,
		mixed $interfaces,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->fields = $fields;
		$this->interfaces = $interfaces;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function getFields() : mixed {
		return $this->fields;
	}

	public function hasInterfaces() : bool {
		return !is_null($this->interfaces);
	}

	public function getInterfaces() : mixed {
		return $this->interfaces;
	}

	public function getKind() : string {
		return 'ObjectTypeDefinition';
	}
}
