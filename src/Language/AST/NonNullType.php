<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;
use InvalidArgumentException;

class NonNullType extends TypeNode {
	private TypeNode $type;

	public function __construct(
		?Location $loc,
		TypeNode $type
	){
		parent::__construct($loc);
		if ($type instanceof NonNullType){
			throw new InvalidArgumentException('Argument $type to NonNullType constructor must not be of type NonNullType');
		}
		$this->type = $type;
	}

	public function getType() : TypeNode {
		return $this->type;
	}
}
