<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

class ListType extends TypeNode {
	private TypeNode $type;

	public function __construct(
		?Location $loc,
		TypeNode $type
	){
		parent::__construct($loc);
		$this->type = $type;
	}

	public function getType() : TypeNode {
		return $this->type;
	}

	public function getKind() : string {
		return 'ListType';
	}
}
