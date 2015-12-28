<?hh // strict

namespace GraphQL;

use GraphQL\Language\Source;
use GraphQL\Language\getLocation;
use GraphQL\Language\SourceLocation;

class SyntaxException extends Exception {
	private SourceLocation $location;
	public function __construct(
		private Source $source,
		private int $position,
		private string $description,
	){
		$location = \GraphQL\Language\getLocation($source, $position);
		$this->location = $location;

		$message =
			"Syntax Error {$source->getName()} ({$location['line']}:{$location['column']}) " .
			$description .
			"\n\n" .
			self::highlightSourceAtLocation($source, $location);

		// Perhaps fix the message that would get shown here somehow.
		// For now, all the information needed to work with this is properly
		// stored on the instance and has getters.
		parent::__construct($message, 0, null);
	}

	public function getSource() : Source {
		return $this->source;
	}

	public function getLocation() : SourceLocation {
		return $this->location;
	}

	public function getDescription() : string {
		return $this->description;
	}

	public function getPosition() : int {
		return $this->position;
	}

	private static function highlightSourceAtLocation(Source $source, SourceLocation $location) : string {
		$body = $source->getBody();
		$lines = preg_split('/\r\n|[\r\n]/u', $body);

		$line = $location['line'];
		$prevLineNum = strval($line-1);
		$lineNum = strval($line);
		$nextLineNum = strval($line+1);
		$padLen = strlen($nextLineNum);

		return (
			($line >= 2 ?
				self::lpad($padLen, $prevLineNum) . ": " . $lines[$line - 2] . "\n" : "") .
			self::lpad($padLen, $lineNum) . ": " . $lines[$line - 1] . "\n" .
			str_repeat(' ', 2 + $padLen + $location['column']) . "^\n" .
			($line < count($lines) ?
				self::lpad($padLen, $nextLineNum) . ': ' . $lines[$line] . "\n" : "")
		);
	}

	private static function lpad(int $len, string $string) : string {
		$length = mb_strlen($string, 'UTF-8');
		return str_repeat(' ', $len - $length + 1) . $string;
	}
}
