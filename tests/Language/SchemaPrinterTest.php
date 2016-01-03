<?hh // strict


namespace GraphQL\Tests\Language;

use GraphQL\Language\AST;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Language\Source;
use PHPUnit_Framework_TestCase;

require_once(__DIR__ . '/../../src/Language/Printer.php');

class SchemaPrinterTest extends PHPUnit_Framework_TestCase {
	private function parse(string $graphql) : AST\Document {
		return (new Parser(new Source($graphql)))->parse();
	}

	/**
	 * @test
	 */
	public function printsMinimalAST() : void {
		$ast = new AST\ScalarTypeDefinition(
			null,
			new AST\Name(null, 'foo')
		);

		$printed = Printer::print($ast);

		$this->assertEquals('scalar foo', $printed);
	}

	public function producesHelpfulErrorMessages() : void {
		// graphql-hack is strongly typed
		// so, test left empty
	}

	/**
	 * @test
	 */
	public function printsKitchenSink() : void {
		$kitchenSink = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'schema-kitchen-sink.graphql');

		$ast = $this->parse($kitchenSink);

		$printed = Printer::print($ast);

		$expected = <<<'GRAPHQL'
type Foo implements Bar {
  one: Type
  two(argument: InputType!): Type
  three(argument: InputType, other: String): Int
  four(argument: String = "string"): String
  five(argument: [String] = ["string", "string"]): String
  six(argument: InputType = {key: "value"}): Type
}

interface Bar {
  one: Type
  four(argument: String = "string"): String
}

union Feed = Story | Article | Advert

scalar CustomScalar

enum Site {
  DESKTOP
  MOBILE
}

input InputType {
  key: String!
  answer: Int = 42
}

extend type Foo {
  seven(argument: [String]): Type
}

GRAPHQL;

		$this->assertEquals($expected, $printed);
	}
}
