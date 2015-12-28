<?hh // strict

namespace GraphQL\Language;

use GraphQL\SyntaxException;

// graphql-js code uses a simple function to lex over and over.
// This is a fine JS idiom, but doesn't translate that great over to
// PHP/Hack. Use a simple class instance as a lexer,
// with the main lex function from graphql-js as "nextToken".

enum TokenKind : int {
	EOF = 1;
    BANG = 2;
    DOLLAR = 3;
    PAREN_L = 4;
    PAREN_R = 5;
    SPREAD = 6;
    COLON = 7;
    EQUALS = 8;
    AT = 9;
    BRACKET_L = 10;
    BRACKET_R = 11;
    BRACE_L = 12;
    PIPE = 13;
    BRACE_R = 14;
    NAME = 15;
    VARIABLE = 16;
    INT = 17;
    FLOAT = 18;
    STRING = 19;
}

type Token = shape(
	'kind' => TokenKind,
	'start' => int,
	'end' => int,
	'value' => ?string,
);


class Lexer {
	private int $prevPosition = 0;
	public function __construct(private Source $source){
		$bom = mb_convert_encoding("\xFE\xFF", 'UTF-8', 'UTF-16BE');

		// Skip BOM
		if (self::substr($source->getBody(), 0, 1) === $bom){
			$this->prevPosition = 1;
		}
	}

	public function nextToken(?int $resetPosition = null) : Token {
		$token = $this->readToken(is_null($resetPosition) ? $this->prevPosition : $resetPosition);
		$this->prevPosition = $token['end'];

		return $token;
	}

	private function readToken(int $fromPosition) : Token {
		$source = $this->source;
		$body = $source->getBody();
		$bodyLength = $source->getLength();

		$position = self::positionAfterWhitespace($source, $fromPosition);

		if ($position >= $bodyLength) {
			return self::makeToken(TokenKind::EOF, $position,$position);
		}

		$char = self::substr($body, $position, 1);

		// Validate we're dealing with a single character (no unicode funny stuff)
		// and that it is a visible character
		if(strlen($char) === 1 && (
			// If it is an invisible character
			// only allow tabs, new lines and carriage returns
			($code = ord($char)) < 32 &&
			$code !== 9 && // tab
			$code !== 10 && // new line
			$code !== 13 // carriage return
		)) {
			throw new SyntaxException($source, $position, "Invalid character " . self::printChar($char));
		}

		switch($char) {
			case '!':
				return self::makeToken(TokenKind::BANG, $position, $position + 1);
			case '$':
				return self::makeToken(TokenKind::DOLLAR, $position, $position + 1);
			case '(':
				return self::makeToken(TokenKind::PAREN_L, $position, $position + 1);
			case ')':
				return self::makeToken(TokenKind::PAREN_R, $position, $position + 1);
			case '.':
				if (self::substr($body, $position, 3) === '...'){
					return self::makeToken(TokenKind::SPREAD, $position, $position + 3);
				}
				break;
			case ':':
				return self::makeToken(TokenKind::COLON, $position, $position + 1);
			case '=':
				return self::makeToken(TokenKind::EQUALS, $position, $position + 1);
			case '@':
				return self::makeToken(TokenKind::AT, $position, $position + 1);
			case '[':
				return self::makeToken(TokenKind::BRACKET_L, $position, $position + 1);
			case ']':
				return self::makeToken(TokenKind::BRACKET_R, $position, $position + 1);
			case '{':
				return self::makeToken(TokenKind::BRACE_L, $position, $position + 1);
			case '|':
				return self::makeToken(TokenKind::PIPE, $position, $position + 1);
			case '}':
				return self::makeToken(TokenKind::BRACE_R, $position, $position + 1);
			// A-Z
			// FALLTHROUGH
			case 'A': case 'B': case 'C': case 'D': case 'E': case 'F': case 'G':
			// FALLTHROUGH
			case 'H': case 'I': case 'J': case 'K': case 'L': case 'M': case 'N':
			// FALLTHROUGH
			case 'O': case 'P': case 'Q': case 'R': case 'S': case 'T': case 'U':
			// FALLTHROUGH
			case 'V': case 'W': case 'X': case 'Y': case 'Z':

			// FALLTHROUGH
			case '_':

			// a-z
			// FALLTHROUGH
			case 'a': case 'b': case 'c': case 'd': case 'e': case 'f': case 'g':
			// FALLTHROUGH
			case 'h': case 'i': case 'j': case 'k': case 'l': case 'm': case 'n':
			// FALLTHROUGH
			case 'o': case 'p': case 'q': case 'r': case 's': case 't': case 'u':
			// FALLTHROUGH
			case 'v': case 'w': case 'x': case 'y':
			case 'z':
				return self::readName($source, $position);

			// FALLTHROUGH
			case '-':
			// FALLTHROUGH
			case '0': case '1': case '2': case '3': case '4':
			case '5': case '6': case '7': case '8':
			case '9':
				return self::readNumber($source, $position, $char);

			case '"':
				return self::readString($source, $position);
		}

		throw new SyntaxException($source, $position, "Unexpected character " . self::printChar($char));
	}

	private static function makeToken(TokenKind $kind, int $start, int $end, ?string $value = null) : Token {
		return shape(
			'kind' => $kind,
			'start' => $start,
			'end' => $end,
			'value' => $value
		);
	}

	private static function positionAfterWhitespace(Source $source, int $startPosition) : int {
		$body = $source->getBody();
		$bodyLength = $source->getLength();
		$position = $startPosition;

		$tab = "\t";
		$space = ' ';

		$newLine = "\n";
		$carriageReturn = "\r";

		$comma = ',';

		$comment = '#';

		while ($position < $bodyLength) {
			$char = self::substr($body, $position, 1);

			if(
				$char === $tab ||
				$char === $space ||
				$char === $newLine ||
				$char === $carriageReturn ||
				$char === $comma
			) {
				++$position;
			} else if ($char === $comment) {
				// Skip comments
				++$position;

				// graphql-js goes character by character at this point.
				// Here is an attempt to avoid doing too many string allocations
				// as there's no way to simply get char codes in PHP/Hack
				// that I know of
				$newLinePosition = mb_strpos($body, $newLine, $position, 'UTF-8');
				$carriageReturnPosition = mb_strpos($body, $carriageReturn, $position, 'UTF-8');

				if($newLinePosition === false && $carriageReturnPosition === false) {
					$position = $bodyLength;
				} else {
					$diff = $newLinePosition - $carriageReturnPosition;

					if ($diff === 1) {
						// New line is after carriage return, use that
						$position = $newLinePosition;
					} else if ($diff === -1) {
						// Carriage return is after new line, use that
						$position = $carriageReturnPosition;
					} else if ($newLinePosition < $carriageReturnPosition || $carriageReturnPosition === false) {
						$position = $newLinePosition;
					} else {
						$position = $carriageReturnPosition;
					}
				}
			} else {
				break;
			}
		}

		return $position;
	}

	private static function readNumber(Source $source, int $start, string $firstChar) : Token {
		$body = $source->getBody();
		$char = $firstChar;
		$isFloat = false;
		$position = $start;

		if ($char === '-') {
			++$position;
			$char = self::nillable_substr($body, $position, 1);
			if (is_null($char)){
				throw new SyntaxException($source, $position, "Invalid number, expected digit but got: " . self::printChar($char) . '.');
			}
		}

		if ($char === '0') {
			++$position;
			$char = self::nillable_substr($body, $position, 1);
			if(in_array($char, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'])){
				throw new SyntaxException(
					$source,
					$position,
					'Invalid number, unexpected digit after 0: ' . self::printChar($char) . '.'
				);
			}
		} else {
			$position = self::readDigits($source, $position, $char);
			$char = self::nillable_substr($body, $position, 1);
		}

		if ($char === '.'){
			$isFloat = true;

			++$position;
			$char = self::nillable_substr($body, $position, 1);
			$position = self::readDigits($source, $position, $char);
			$char = self::nillable_substr($body, $position, 1);
		}

		if ($char === 'E' || $char === 'e') {
			$isFloat = true;

			++$position;
			$char = self::nillable_substr($body, $position, 1);

			if ($char === '+' || $char === '-') {
				++$position;
				$char = self::nillable_substr($body, $position, 1);
			}

			$position = self::readDigits($source, $position, $char);
		}

		return self::makeToken(
			$isFloat ? TokenKind::FLOAT : TokenKind::INT,
			$start,
			$position,
			self::substr($body, $start, $position - $start)
		);
	}

	private static function substr(string $str, int $start, int $len) : string {
		return mb_substr($str, $start, $len, 'UTF-8');
	}

	private static function nillable_substr(string $str, int $start, int $len) : ?string {
		$res = self::substr($str, $start, $len);
		if($res === ""){
			return null;
		}

		return $res;
	}

	private static function readDigits(Source $source, int $start, ?string $firstChar) : int {
		$body = $source->getBody();
		$position = $start;
		$char = $firstChar;

		if(strlen($char) > 1){
			throw new SyntaxException(
				$source,
				$position,
				"Invalid number, expected digit but got: " . self::printChar($char) . '.'
			);
		}


		$code = ord($char ?? '');

		// 48 = 0, 57 = 9
		if($code >= 48 && $code <= 57){
			do {
				++$position;
				$char = self::substr($body, $position, 1);
			} while(strlen($char) === 1 && ($code = ord($char)) >= 48 && $code <= 57);
			return $position;
		}

		throw new SyntaxException(
			$source,
			$position,
			"Invalid number, expected digit but got: " . self::printChar($char) . '.'
		);
	}

	private static function printChar(?string $char) : string {
		return is_null($char) ? '<EOF>' :
			json_encode($char);
	}

	private static function readName(Source $source, int $position) : Token {
		$body = $source->getBody();
		$bodyLength = $source->getLength();

		$end = $position + 1;
		while(
			$end !== $bodyLength &&
			strlen($char = self::substr($body, $end, 1)) === 1 &&
			(
				($code = ord($char)) === 95 || // _
				$code >= 48 && $code <= 57 || // 0-9
				$code >= 65 && $code <= 90 || // A-Z
				$code >= 97 && $code <= 122 // a-z
			)
		){
			++$end;
		}


		return self::makeToken(
			TokenKind::NAME,
			$position,
			$end,
			self::substr($body, $position, $end - $position)
		);
	}

	private static function readString(Source $source, int $start) : Token {
		$body = $source->getBody();
		$bodyLength = $source->getLength();

		$position = $start + 1;
		$chunkStart = $position;

		$value = '';
		$code = 0;
		$char = '';

		while(
			$position < $bodyLength &&
			// No line terminators
			($char = self::substr($body, $position, 1)) !== "\n" &&
			$char !== "\r" &&
			// No quotes
			$char !== '"'
		) {
			if(
				// Invisible characters
				($code = ord($char)) < 32 &&
				$code != 9 // space is ok
			) {
				throw new SyntaxException(
					$source,
					$position,
					'Invalid character within String: ' . self::printChar($char) . '.'
				);
			}

			++$position;
			if ($code == 92) { // \
				$value .= self::substr($body, $chunkStart, $position - 1 - $chunkStart);
				$char = self::nillable_substr($body, $position, 1);
				if(is_null($char) || strlen($char) > 1){
					break;
				}
				$code = ord($char);

				switch($code){
					case 34: // "
						$value .= '"';
						break;
					case 47: // /
						$value .= '/';
						break;
					case 92: // \
						$value .= "\\";
						break;
					case 98: // b
						$value .= chr(8);
						break;
					case 102: // f
						$value .= chr(12);
						break;
					case 110: // n
						$value .= "\n";
						break;
					case 114: // r
						$value .= "\r";
						break;
					case 116: // t
						$value .= "\t";
						break;
					case 117: // u
						// We have no fromCharCode function in PHP/Hack
						// Let's use mb_convert_encoding to convert from
						// utf-16 big endian to utf-8

						$first = (self::char2hex(self::substr($body, $position + 1, 1)) << 4) | (self::char2hex(self::substr($body, $position + 2, 1)));
						$second = (self::char2hex(self::substr($body, $position + 3, 1)) << 4) | (self::char2hex(self::substr($body, $position + 4, 1)));

						if($first < 0 || $second < 0){
							throw new SyntaxException(
								$source,
								$position,
								'Invalid character escape sequence: \\u' .
								mb_substr($body, $position + 1, 4) . '.',
							);
						}
						$escape = chr($first) . chr($second);
						$value .= mb_convert_encoding($escape, 'UTF-8', 'UTF-16BE');
						$position += 4;
						break;
					default:
						throw new SyntaxException(
							$source,
							$position,
							'Invalid character escape sequence: \\' .
							$char . '.'
						);
				}
				++$position;
				$chunkStart = $position;
			}
		}

		if ($char !== '"'){
			throw new SyntaxException($source, $position, 'Unterminated string.');
		}

		$value .= self::substr($body, $chunkStart, $position - $chunkStart);

		return self::makeToken(TokenKind::STRING, $start, $position + 1, $value);
	}

	private static function char2hex(string $char) : int{
		if (strlen($char) !== 1){
			return -1;
		}
		$code = ord($char);

		if ($code >= 48 && $code <= 57) { // 0-9
		 	return $code - 48;
		} elseif ($code >= 65 && $code <= 70) { // A-F
			return $code - 55;
		} elseif ($code >= 97 && $code <= 102) { // a-f
			return $code - 87;
		} else {
			return -1;
		}
	}
}
