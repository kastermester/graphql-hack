<?hh // strict

namespace GraphQL\Language\AST;

use ConstVector;
use GraphQL\Language\Location;

class FragmentSpread extends Selection {
	private Name $name;
	private ?ConstVector<Directive> $directives;

	public function __construct(
		?Location $loc,
		Name $name,
		?ConstVector<Directive> $directives
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->directives = $directives;
	}

	public function getName() : Name {
		return $this->name;
	}

	public function hasDirectives() : bool {
		return !is_null($this->directives);
	}

	public function getDirectives() : ?ConstVector<Directive> {
		return $this->directives;
	}

	public function getKind() : string {
		return 'FragmentSpread';
	}
}
