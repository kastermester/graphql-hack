<?hh // strict

use GraphQL\Language\Lexer;
use GraphQL\Language\Source;
use GraphQL\Language\TokenKind;
use GraphQL\Language\Token;
use GraphQL\SyntaxException;

class LexerTest extends PHPUnit_Framework_TestCase {
	private function assertTokenEquals(Token $token, TokenKind $kind, int $start, int $end, ?string $value = null) : void {
		$this->assertEquals($kind, $token['kind'], 'Token kind mismatch');
		$this->assertEquals($start, $token['start'], 'Token start mismatch');
		$this->assertEquals($end, $token['end'], 'Token end mismatch');
		$this->assertEquals($value, $token['value'], 'Token value mismatch');
	}

	private function lexOne(string $source) : Token {
		return (new Lexer(new Source($source)))->nextToken();
	}
	/**
	 * @test
	 * @expectedException GraphQL\SyntaxException
	 * @expectedExceptionMessageRegExp /Syntax Error GraphQL \(1:1\) Invalid character "\\u0007"/
	 */
	public function disallowsUncommonControlChars() : void {
		$this->lexOne(mb_convert_encoding("\x00\x07", "UTF-8", 'UTF-16BE'));
	}

	/**
	 * @test
	 */
	public function acceptsBOMHeader() : void {
		$this->assertTokenEquals(
			$this->lexOne(mb_convert_encoding("\xFE\xFF", 'UTF-8', 'UTF-16BE') . " foo"),
			TokenKind::NAME,
			2,
			5,
			'foo'
		);
	}

	/**
	 * @test
	 */
	 public function skipsWhitespace() : void {
		 $token = $this->lexOne(<<<TOKENS


    foo


TOKENS
		);
		$this->assertTokenEquals($token, TokenKind::NAME, 6, 9, 'foo');

		$token = $this->lexOne(<<<TOKENS

    #comment
    foo#comment

TOKENS
		);
		$this->assertTokenEquals($token, TokenKind::NAME, 18, 21, 'foo');

		$token = $this->lexOne(',,,foo,,,');
		$this->assertTokenEquals($token, TokenKind::NAME, 3, 6, 'foo');
	 }

	 /**
	  * @test
	  */
	 public function errorsRespectWhitespace() : void {
		try {
			$token = $this->lexOne(<<<TOKENS


    ?


TOKENS
		   );
		} catch (SyntaxException $ex) {
			$this->assertEquals(
				"Syntax Error GraphQL (3:5) Unexpected character \"?\"\n" .
				"\n" .
				" 2: \n" .
				" 3:     ?\n" .
				"        ^\n" .
				" 4: \n"
				, $ex->getMessage());
			return;
		}

		$this->assertFalse(true, "Expected exception of type GraphQL\\SyntaxException to be thrown");
	 }

	 /**
	  * @test
	  * @dataProvider stringProvider
	  */
	 public function lexesStrings(string $source, string $expected) : void {
		 $this->assertTokenEquals($this->lexOne($source), TokenKind::STRING, 0, mb_strlen($source), $expected);
	 }

	 public function stringProvider() : array<(string, string)> {
		 return [
			tuple('"simple"', 'simple'),
			tuple('" white space "', ' white space '),
			tuple('"quote \\""', 'quote "'),
			tuple('"escaped \n\r\b\t\f"', "escaped \n\r\010\t\014"),
			tuple('"slashes \\\\ \\/"', 'slashes \\ /'),
			tuple('"unicode \\u1234\\u5678\\u90AB\\uCDEF"', json_decode("\"unicode \\u1234\\u5678\\u90AB\\uCDEF\"")),
			// Extra test added to ensure we allow strings with multibyte characters
			tuple('"' . json_decode('"\u0A0A"')  .'"', json_decode('"\u0A0A"')),
		];
	 }

	 /**
	  * @test
	  * @dataProvider stringErrorProvider
	  */
	 public function lexReportsUsefulStringErrors(string $source, string $expectedError) : void {
		try {
 			$this->lexOne($source);
 		} catch (SyntaxException $ex) {
 			$this->assertStringStartsWith($expectedError, $ex->getMessage(), 'Expected error message to be a prefix');
 			return;
 		}

 		$this->assertFalse(true, "Expected exception to be thrown");
	 }

	 public function stringErrorProvider() : array<(string, string)> {
		return [
			tuple('"', 'Syntax Error GraphQL (1:2) Unterminated string'),
			tuple('"no end quote', 'Syntax Error GraphQL (1:14) Unterminated string'),
			tuple("\"contains unescaped \x07 control char\"", 'Syntax Error GraphQL (1:21) Invalid character within String: "\\u0007".'),
			tuple("\"null-byte is not \x00 end of file\"", 'Syntax Error GraphQL (1:19) Invalid character within String: "\\u0000".'),
			tuple("\"multi\nline\"", 'Syntax Error GraphQL (1:7) Unterminated string'),
			tuple("\"multi\rline\"", 'Syntax Error GraphQL (1:7) Unterminated string'),
			tuple('"bad \\z esc"', 'Syntax Error GraphQL (1:7) Invalid character escape sequence: \\z.'),
			tuple('"bad \\x esc"', 'Syntax Error GraphQL (1:7) Invalid character escape sequence: \\x.'),
			tuple('"bad \\u1 esc"', 'Syntax Error GraphQL (1:7) Invalid character escape sequence: \\u1 es.'),
			tuple('"bad \\u0XX1 esc"', 'Syntax Error GraphQL (1:7) Invalid character escape sequence: \\u0XX1.'),
			tuple('"bad \\uXXXX esc"', 'Syntax Error GraphQL (1:7) Invalid character escape sequence: \\uXXXX.'),
			tuple('"bad \\uFXXX esc"', 'Syntax Error GraphQL (1:7) Invalid character escape sequence: \\uFXXX.'),
			tuple('"bad \\uXXXF esc"', 'Syntax Error GraphQL (1:7) Invalid character escape sequence: \\uXXXF.'),
		];
	 }

	 /**
	  * @test
	  * @dataProvider numberProvider
	  */
	 public function lexesNumbers(string $number, TokenKind $expectedKind, int $expectedStart, int $expectedEnd) : void {
		 $this->assertTokenEquals($this->lexOne($number), $expectedKind, $expectedStart, $expectedEnd, $number);
	 }

	 public function numberProvider() : array<(string, TokenKind, int, int)> {
		return [
			tuple('4', TokenKind::INT, 0, 1),
			tuple('4.123', TokenKind::FLOAT, 0, 5),
			tuple('-4', TokenKind::INT, 0, 2),
			tuple('9', TokenKind::INT, 0, 1),
			tuple('0', TokenKind::INT, 0, 1),
			tuple('-4.123', TokenKind::FLOAT, 0, 6),
			tuple('0.123', TokenKind::FLOAT, 0, 5),
			tuple('123e4', TokenKind::FLOAT, 0, 5),
			tuple('123E4', TokenKind::FLOAT, 0, 5),
			tuple('123e-4', TokenKind::FLOAT, 0, 6),
			tuple('123e+4', TokenKind::FLOAT, 0, 6),
			tuple('-1.123e4', TokenKind::FLOAT, 0, 8),
			tuple('-1.123E4', TokenKind::FLOAT, 0, 8),
			tuple('-1.123e-4', TokenKind::FLOAT, 0, 9),
			tuple('-1.123e+4', TokenKind::FLOAT, 0, 9),
			tuple('-1.123e4567', TokenKind::FLOAT, 0, 11),

		];
	 }

	 /**
	  * @test
	  * @dataProvider numberErrorProvider
	  */
	 public function lexReportsUsefulNumberErrors(string $source, string $expectedError) : void {
		try {
 			$this->lexOne($source);
 		} catch (SyntaxException $ex) {
 			$this->assertStringStartsWith($expectedError, $ex->getMessage(), 'Expected error message to be a prefix');
 			return;
 		}

 		$this->assertFalse(true, "Expected exception to be thrown");
	 }

	 public function numberErrorProvider() : array<(string, string)> {
		 return [
			tuple('00', 'Syntax Error GraphQL (1:2) Invalid number, unexpected digit after 0: "0"'),
			tuple('+1', 'Syntax Error GraphQL (1:1) Unexpected character "+"'),
			tuple('1.', 'Syntax Error GraphQL (1:3) Invalid number, expected digit but got: <EOF>.'),
			tuple('.123', 'Syntax Error GraphQL (1:1) Unexpected character "."'),
		    tuple('1.A',
		      'Syntax Error GraphQL (1:3) Invalid number, ' .
		      'expected digit but got: "A".'
		  	),
			tuple('-A',
				'Syntax Error GraphQL (1:2) Invalid number, ' .
				'expected digit but got: "A".'
			),
		    tuple('1.0e',
				'Syntax Error GraphQL (1:5) Invalid number, ' .
				'expected digit but got: <EOF>.'
			),
		    tuple('1.0eA',
				'Syntax Error GraphQL (1:5) Invalid number, ' .
				'expected digit but got: "A".'
			),
		];
	 }

	 /**
	  * @test
	  * @dataProvider punctuationProvider
	  */
	 public function lexesPunctuation(string $source, TokenKind $kind) : void {
		 $this->assertTokenEquals($this->lexOne($source), $kind, 0, strlen($source), null);
	 }

	 public function punctuationProvider() : array<(string, TokenKind)> {
		 return [
			tuple('!', TokenKind::BANG),
			tuple('$', TokenKind::DOLLAR),
			tuple('(', TokenKind::PAREN_L),
			tuple(')', TokenKind::PAREN_R),
			tuple('...', TokenKind::SPREAD),
			tuple(':', TokenKind::COLON),
			tuple('=', TokenKind::EQUALS),
			tuple('@', TokenKind::AT),
			tuple('[', TokenKind::BRACKET_L),
			tuple(']', TokenKind::BRACKET_R),
			tuple('{', TokenKind::BRACE_L),
			tuple('|', TokenKind::PIPE),
			tuple('}', TokenKind::BRACE_R),
		];
	 }

	 /**
	  * @test
	  * @dataProvider unknownCharacterErrors
	  */
	 public function lexReportsUsefulUnknownCharacterError(string $source, string $expectedError) : void {
		try {
 			$this->lexOne($source);
 		} catch (SyntaxException $ex) {
 			$this->assertStringStartsWith($expectedError, $ex->getMessage(), 'Expected error message to be a prefix');
 			return;
 		}

 		$this->assertFalse(true, "Expected exception to be thrown");
	 }

	 public function unknownCharacterErrors() : array<(string, string)> {
		return [
			tuple('..', 'Syntax Error GraphQL (1:1) Unexpected character "."'),
			tuple('?', 'Syntax Error GraphQL (1:1) Unexpected character "?"'),
			tuple(mb_convert_encoding("\x20\x3B", 'UTF-8', 'UTF-16BE'), 'Syntax Error GraphQL (1:1) Unexpected character "\u203b"'),
			tuple(mb_convert_encoding("\x20\x0b", 'UTF-8', 'UTF-16BE'), 'Syntax Error GraphQL (1:1) Unexpected character "\u200b"'),
		];
	 }

	 /**
	  * @test
	  * @expectedException GraphQL\SyntaxException
	  * @expectedExceptionMessageRegExp /Syntax Error GraphQL \(1:3\) Invalid number, expected digit but got: "b"\./
	  */
	 public function lexReportsUsefulInformationForDashesInNames() : void {
		 $lexer = new Lexer(new Source('a-b'));
		 $firstToken = $lexer->nextToken();

		 $this->assertTokenEquals($firstToken, TokenKind::NAME, 0, 1, 'a');
		 $lexer->nextToken();
	 }
}
