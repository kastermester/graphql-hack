<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class ObjectTypeDefinition extends TypeDefinition {
	private Name $name;
	private ConstVector<FieldDefinition> $fields;
	private ?ConstVector<NamedType> $interfaces;

	public function __construct(
		?Location $loc,
		Name $name,
		ConstVector<FieldDefinition> $fields,
		?ConstVector<NamedType> $interfaces,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->fields = $fields;
		$this->interfaces = $interfaces;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getFields() : ConstVector<FieldDefinition> {
		return $this->fields;
	}

	public function hasInterfaces() : bool {
		return !is_null($this->interfaces);
	}

	public function getInterfaces() : ?ConstVector<NamedType> {
		return $this->interfaces;
	}
}
