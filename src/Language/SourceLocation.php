<?hh // strict
namespace GraphQL\Language;

type SourceLocation = shape(
	'line' => int,
	'column' => int,
);

function getLocation(Source $source, int $position) : SourceLocation {
	$body = $source->getBody();
	$length = $source->getLength();

	$line = 1;

	$offset = 0;
	while($offset < $position) {
		$newLinePosition = mb_strpos($body, "\n", $offset, 'UTF-8');
		$carriageReturnPosition = mb_strpos($body, "\r", $offset, 'UTF-8');

		if($newLinePosition === false){
			if ($carriageReturnPosition === false || $carriageReturnPosition >= $position){
				break;
			}
		} elseif ($carriageReturnPosition === false) {
			if ($newLinePosition === false || $newLinePosition >= $position){
				break;
			}
		} elseif($carriageReturnPosition > $position && $newLinePosition >= $position){
			break;
		}

		// Linebreak is \r\n
		if (($newLinePosition - $carriageReturnPosition === 1) && $newLinePosition !== false && $carriageReturnPosition !== false){
			++$line;
			$offset = $carriageReturnPosition + 1;
		} else if ($newLinePosition !== false && ($carriageReturnPosition === false || $newLinePosition < $carriageReturnPosition)){
			++$line;
			$offset = $newLinePosition + 1;
		} else {
			++$line;
			$offset = $carriageReturnPosition + 1;
		}
	}

	$column = $position + 1 - $offset;

	return shape('line' => $line, 'column' => $column);
}
