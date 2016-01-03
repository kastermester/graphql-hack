<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class InterfaceTypeDefinition extends TypeDefinition {
	private Name $name;
	private ConstVector<FieldDefinition> $fields;

	public function __construct(
		?Location $loc,
		Name $name,
		ConstVector<FieldDefinition> $fields,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->fields = $fields;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getFields() : ConstVector<FieldDefinition> {
		return $this->fields;
	}

	public function getKind() : string {
		return 'InterfaceTypeDefinition';
	}
}
