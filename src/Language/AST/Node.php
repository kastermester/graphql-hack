<?hh // strict

namespace GraphQL\Language\AST;

use GraphQL\Language\Location;

// graphql-js uses plain object types for this
// this would be ideal here as well, but as we cannot (can we?)
// have discriminated type unions yet, this seems undoable in Hack.
// Alas, have to resort to a class hierarchy

abstract class Node {
	private ?Location $loc;

	public final function hasLocation() : bool {
		return !is_null($this->loc);
	}

	public final function getLocation() : ?Location {
		return $this->loc;
	}

	protected function __construct(?Location $loc) {
		$this->loc = $loc;
	}

	public abstract function getKind() : string;
}
