<?hh // strict

namespace GraphQL\Language;

class Location {
	private int $start;
	private int $end;

	private ?Source $source;

	public function __construct(int $start, int $end, ?Source $source = null){
		$this->start = $start;
		$this->end = $end;
		$this->source = $source;
	}

	public function getStart() : int {
		return $this->start;
	}

	public function getEnd() : int {
		return $this->end;
	}

	public function getSource() : ?Source {
		return $this->source;
	}
}
