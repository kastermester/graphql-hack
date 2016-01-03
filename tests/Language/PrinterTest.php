<?hh // strict

namespace GraphQL\Tests\Language;

use GraphQL\Language\AST;
use GraphQL\Language\Parser;
use GraphQL\Language\Printer;
use GraphQL\Language\Source;
use PHPUnit_Framework_TestCase;

require_once(__DIR__ . '/../../src/Language/Printer.php');

class PrinterTest extends PHPUnit_Framework_TestCase {
	private function parse(string $graphql) : AST\Document {
		return (new Parser(new Source($graphql)))->parse();
	}


	public function doesNotAlterAst() : void {
		// graphql-hack AST is immutable.
		// no test needed
	}

	/**
	 * @test
	 */
	public function printsMinimalAST() : void {
		$ast = new AST\Field(
			null,
			new AST\Name(null, 'foo'),
			null,
			null,
			null,
			null
		);

		$printed = Printer::print($ast);

		$this->assertEquals('foo', $printed);
	}

	public function producesHelpfulErrorMessages() : void {
		// graphql-hack is strongly typed
		// so, test left empty
	}

	/**
	 * @test
	 */
	public function printsKitchenSink() : void {
		$kitchenSink = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'kitchen-sink.graphql');

		$ast = $this->parse($kitchenSink);

		$printed = Printer::print($ast);

		$expected = <<<'GRAPHQL'
query queryName($foo: ComplexType, $site: Site = MOBILE) {
  whoever123is: node(id: [123, 456]) {
    id
    ... on User @defer {
      field2 {
        id
        alias: field1(first: 10, after: $foo) @include(if: $foo) {
          id
          ...frag
        }
      }
    }
    ... @skip(unless: $foo) {
      id
    }
    ... {
      id
    }
  }
}

mutation likeStory {
  like(story: 123) @defer {
    story {
      id
    }
  }
}

subscription StoryLikeSubscription($input: StoryLikeSubscribeInput) {
  storyLikeSubscribe(input: $input) {
    story {
      likers {
        count
      }
      likeSentence {
        text
      }
    }
  }
}

fragment frag on Friend {
  foo(size: $size, bar: $b, obj: {key: "value"})
}

{
  unnamed(truthy: true, falsey: false)
  query
}

GRAPHQL;

		$this->assertEquals($expected, $printed);
	}
}
