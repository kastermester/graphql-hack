<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class ListType extends TypeNode {
	private mixed $type;

	public function __construct(
		?Location $loc,
		mixed $type
	){
		parent::__construct($loc);
		$this->type = $type;
	}

	public function getType() : mixed {
		return $this->type;
	}

	public function getKind() : string {
		return 'ListType';
	}
}
