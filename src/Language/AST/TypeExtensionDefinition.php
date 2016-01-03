<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class TypeExtensionDefinition extends TypeDefinition {
	private ObjectTypeDefinition $definition;

	public function __construct(
		?Location $loc,
		ObjectTypeDefinition $definition,
	){
		parent::__construct($loc);
		$this->definition = $definition;
	}

	public function getDefinition() : ObjectTypeDefinition {
		return $this->definition;
	}

	public function getKind() : string {
		return 'TypeExtensionDefinition';
	}
}
