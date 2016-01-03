<?hh

namespace GraphQL\Language\AST\Untyped;

use GraphQL\Language\Location;

class InputValueDefinition extends TypeDefinition {
	private mixed $name;
	private mixed $type;
	private mixed $defaultValue;

	public function __construct(
		?Location $loc,
		mixed $name,
		mixed $type,
		mixed $defaultValue,
	){
		parent::__construct($loc);
		$this->name = $name;
		$this->type = $type;
		$this->defaultValue = $defaultValue;
	}

	public function getName() : mixed {
		return $this->name;
	}

	public function getType() : mixed {
		return $this->type;
	}

	public function hasDefaultValue() : bool {
		return !is_null($this->defaultValue);
	}

	public function getDefaultValue() : mixed {
		return $this->defaultValue;
	}

	public function getKind() : string {
		return 'InputValueDefinition';
	}
}
