<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class TypeExtensionDefinition extends TypeDefinition {
	private mixed $definition;

	public function __construct(
		?Location $loc,
		mixed $definition,
	){
		parent::__construct($loc);
		$this->definition = $definition;
	}

	public function getDefinition() : mixed {
		return $this->definition;
	}

	public function getKind() : string {
		return 'TypeExtensionDefinition';
	}
}
