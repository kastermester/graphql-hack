<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class TypeExtensionDefinition extends TypeDefinition {
	private Name $name;
	private ObjectTypeDefinition $definition;

	public function __construct(
		?Location $loc,
		Name $name,
		ObjectTypeDefinition $definition,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->definition = $definition;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function getDefinition() : ObjectTypeDefinition {
		return $this->definition;
	}
}
