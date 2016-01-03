<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\AST\Node;
use GraphQL\Language\Location;

class UnionTypeDefinition extends TypeDefinition {
	private mixed $name;
	private mixed $types;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $types,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->types = $types;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function getTypes() : mixed {
		return $this->types;
	}

	public function getKind() : string {
		return 'UnionTypeDefinition';
	}
}
