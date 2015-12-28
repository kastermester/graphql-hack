<?hh // strict

namespace GraphQL\Language;

use GraphQL\SyntaxException;

/**
 * A representation of source input to GraphQL. The name is optional,
 * but is mostly useful for clients who store GraphQL documents in
 * source files; for example, if the GraphQL input is in a file Foo.graphql,
 * it might be useful for name to be "Foo.graphql".
 */
class Source {
	private string $body;
	private string $name;
	public function __construct(string $body, string $name = 'GraphQL'){
		$this->body = $body;
		$this->name = $name;
	}

	public function getBody() : string {
		return $this->body;
	}

	public function getLength() : int {
		$len = mb_strlen($this->body, 'UTF-8');
		if ($len === false) {
			throw new SyntaxException(
				$this,
				0,
				'Invalid UTF-8 input. Could not determine length of input'
			);
		}

		return $len;
	}

	public function getName() : string {
		return $this->name;
	}
}
