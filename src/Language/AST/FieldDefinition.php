<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class FieldDefinition extends TypeDefinition {
	private Name $name;
	private ConstVector<InputValueDefinition> $arguments;
	private TypeNode $type;

	public function __construct(
		?Location $loc,
		Name $name,
		ConstVector<InputValueDefinition> $arguments,
		TypeNode $type,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->arguments = $arguments;
		$this->type = $type;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getArguments() : ConstVector<InputValueDefinition> {
		return $this->arguments;
	}

	public function getType() : TypeNode {
		return $this->type;
	}

	public function getKind() : string {
		return 'FieldDefinition';
	}
}
