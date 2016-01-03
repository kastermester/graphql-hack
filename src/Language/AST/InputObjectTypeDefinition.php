<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class InputObjectTypeDefinition extends TypeDefinition {
	private Name $name;
	private ConstVector<InputValueDefinition> $fields;

	public function __construct(
		?Location $loc,
		Name $name,
		ConstVector<InputValueDefinition> $fields,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->fields = $fields;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getFields() : ConstVector<InputValueDefinition> {
		return $this->fields;
	}

	public function getKind() : string {
		return 'InputObjectTypeDefinition';
	}
}
