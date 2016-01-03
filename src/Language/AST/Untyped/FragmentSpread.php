<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class FragmentSpread extends Selection {
	private mixed $name;
	private mixed $directives;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $directives
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->directives = $directives;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function hasDirectives() : bool {
		return !is_null($this->directives);
	}

	public function getDirectives() : mixed {
		return $this->directives;
	}

	public function getKind() : string {
		return 'FragmentSpread';
	}
}
