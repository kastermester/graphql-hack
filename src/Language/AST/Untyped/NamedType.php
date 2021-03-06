<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class NamedType extends TypeNode {
	private mixed $name;

	public function __construct(
		?Location $loc,
		mixed $name
	){
		parent::__construct($loc);
		$this->name = $name;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function getKind() : string {
		return 'NamedType';
	}
}
