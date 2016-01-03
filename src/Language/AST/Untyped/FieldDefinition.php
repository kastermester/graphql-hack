<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class FieldDefinition extends TypeDefinition {
	private mixed $name;
	private mixed $arguments;
	private mixed $type;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $arguments,
		mixed $type,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->arguments = $arguments;
		$this->type = $type;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function getArguments() : mixed {
		return $this->arguments;
	}

	public function getType() : mixed {
		return $this->type;
	}

	public function getKind() : string {
		return 'FieldDefinition';
	}
}
