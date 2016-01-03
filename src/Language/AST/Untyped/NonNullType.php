<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;
use InvalidArgumentException;

class NonNullType extends TypeNode {
	private mixed $type;

	public function __construct(
		?Location $loc,
		mixed $type
	){
		parent::__construct($loc);
		if ($type instanceof NonNullType){
			throw new InvalidArgumentException('Argument $type to NonNullType constructor must not be of type NonNullType');
		}
		$this->type = $type;
	}

	public function getType() : mixed {
		return $this->type;
	}

	public function getKind() : string {
		return 'NonNullType';
	}
}
