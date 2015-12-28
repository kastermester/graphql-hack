<?hh // strict

namespace GraphQL\Tests\Language;

use GraphQL\Assert;
use GraphQL\Language\AST;
use GraphQL\Language\Location;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\SourceLocation;
use GraphQL\SyntaxException;
use HH\ImmVector;
use PHPUnit_Framework_TestCase;

class ParserTest extends PHPUnit_Framework_TestCase {
	private function parse(string $source) : AST\Document {
		$parser = new Parser(new Source($source));
		return $parser->parse();
	}
	/**
	 * @test
	 */
	 public function acceptsOptionToNotIncludeSource() : void {
		$parser = new Parser(new Source('{ field }'), false, true);
		$document = $parser->parse();
		$expected = new AST\Document(
			new Location(0, 9),
			ImmVector {
				new AST\OperationDefinition(
					new Location(0, 9),
					AST\Operation::QUERY,
					new AST\SelectionSet (
						new Location(0, 9),
						ImmVector {
							new AST\Field(
								new Location(2, 7),
								new AST\Name(
									new Location(2, 7),
									'field'
								),
								null,
								ImmVector { },
								ImmVector { },
								null,
							),
						},
					),
					null,
					null,
					ImmVector { },
				)
			}
		);

		$this->assertEquals($expected, $document);
	 }

	 /**
	  * @test
	  */
	 public function parseProvidesUsefulErrorWithDetailedInformation() : void {
		 $caughtError = null;
		 try {
			 $this->parse('{');
		 } catch (SyntaxException $ex) {
			 $caughtError = $ex;
		 }

		 $this->assertNotNull($caughtError, 'Expected exception to be thrown');
		 $caughtError = Assert::isNonNull($caughtError);

		 $this->assertEquals(
			"Syntax Error GraphQL (1:2) Expected Name, found EOF\n" .
			"\n" .
			" 1: {\n" .
			"     ^\n"
		 , $caughtError->getMessage());

		 $this->assertEquals(1, $caughtError->getPosition());
		 $this->assertEquals(shape('line' => 1, 'column' => 2), $caughtError->getLocation());
	 }

	 /**
	  * @test
	  * @dataProvider usefulErrorsProvider
	  */
	 public function parseProvidesUsefulErrors(string $source, string $expectedError) : void {
		try {
			$this->parse($source);
		} catch (SyntaxException $ex) {
			$this->assertEquals($expectedError, substr($ex->getMessage(), 0, strlen($expectedError)), 'Expected error message to be a prefix');
			return;
		}

		$this->assertFalse(true, "Expected exception to be thrown");
	 }

	 public function usefulErrorsProvider() : array<(string, string)> {
		 return [
			 tuple(
				"{ ...MissingOn }\nfragment MissingOn Type\n",
				'Syntax Error GraphQL (2:20) Expected "on", found Name "Type"',
			),
			tuple(
				'{ field: {} }',
				'Syntax Error GraphQL (1:10) Expected Name, found {',
			),
			tuple(
				'notanoperation Foo { field }',
				'Syntax Error GraphQL (1:1) Unexpected Name "notanoperation"',
			),
			tuple(
				'...',
				'Syntax Error GraphQL (1:1) Unexpected ...',
			),
		];
	 }

	 /**
	  * @test
	  */
	 public function parseProvidesUsefulErrorWhenUsingSource() : void {
		$parser = new Parser(new Source('query', 'MyQuery.graphql'));
		try {
			$parser->parse();
		} catch (SyntaxException $ex) {
			$expectedError = 'Syntax Error MyQuery.graphql (1:6) Expected {, found EOF';
			$this->assertStringStartsWith($expectedError, $ex->getMessage(), 'Expected error message to be a prefix');
			return;
		}

		$this->assertFalse(true);
	 }

	 /**
	  * @test
	  */
	 public function parsesInlineValues() : void {
		$this->parse('{ field(complex: { a: { b: [ $var ] } }) }');
		$this->assertTrue(true);
	 }

	 /**
	  * @test
	  * @expectedException GraphQL\SyntaxException
	  * @expectedExceptionMessageRegExp /^Syntax Error GraphQL \(1:37\) Unexpected \$/
	  */
	 public function parsesConstantDefaultValues() : void {
		$this->parse('query Foo($x: Complex = { a: { b: [ $var ] } }) { field }');
	 }

	/**
	  * @test
	  * @expectedException GraphQL\SyntaxException
	  * @expectedExceptionMessageRegExp /^Syntax Error GraphQL \(1:10\) Unexpected Name "on"/
	  */
	public function doesNotAcceptFragmentsNamedOn() : void {
		$this->parse('fragment on on on { on }');
	}

	/**
	  * @test
	  * @expectedException GraphQL\SyntaxException
	  * @expectedExceptionMessageRegExp /^Syntax Error GraphQL \(1:9\) Expected Name, found \}/
	  */
	public function doesNotAcceptFragmentsSpreadOfOn() : void {
		$this->parse('{ ...on }');
	}

	/**
	  * @test
	  * @expectedException GraphQL\SyntaxException
	  * @expectedExceptionMessageRegExp /^Syntax Error GraphQL \(1:39\) Unexpected Name "null"/
	  */
	public function doesNotAllowNullAsValue() : void {
		$this->parse('{ fieldWithNullableStringInput(input: null) }');
	}


 	/**
 	  * @test
 	  */
 	public function parsesMultiByteCharacters() : void {
		$multibyte = mb_convert_encoding("\X0A\X0A", 'UTF-8', 'UTF-16BE');
 		$doc = $this->parse(<<<GRAPHQL

        # This comment has a \u0A0A multi-byte character.
        { field(arg: "Has a $multibyte multi-byte character.") }
GRAPHQL
		);

		$definitions = $doc->getDefinitions();
		$this->assertEquals(1, $definitions->count());
		$definition = Assert::isInstanceOf($definitions[0], AST\OperationDefinition::class);
		$selectionSet = $definition->getSelectionSet();
		$selections = $selectionSet->getSelections();
		$this->assertEquals(1, $selections->count());
		$field = Assert::isInstanceOf($selections[0], AST\Field::class);

		$arguments = $field->getArguments();
		$arguments = Assert::isNonNull($arguments);
		$this->assertEquals(1, $arguments->count());

		$argument = $arguments[0];
		$this->assertEquals('arg', $argument->getName()->getValue());
		$value = $argument->getValue();
		$value = Assert::isInstanceOf($value, AST\StringValue::class);
		$this->assertEquals("Has a $multibyte multi-byte character.", $value->getValue());
 	}


	 /**
	 * @test
	 */
	 public function parsesKitchenSink() : void {
		 $this->parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'kitchen-sink.graphql'));
		 // No exception is good business here
		 $this->assertTrue(true);
	 }

	/**
	 * @test
	 * @dataProvider nonKeywordsProvider
	 */
	public function allowsNonKeywordsAnywhereANameIsAllowed(string $nonKeyword) : void {
		$fragmentName = $nonKeyword;
		if ($nonKeyword === 'on') {
			// on cannot be a fragment name
			$fragmentName = 'a';
		}

		$this->parse(
			"query $nonKeyword {\n" .
			"  ... $fragmentName\n" .
			"  ... on $nonKeyword { field }\n" .
			"}\n" .
			"fragment $fragmentName on Type {\n" .
			"  $nonKeyword($nonKeyword: \$$nonKeyword) @$nonKeyword($nonKeyword: $nonKeyword)\n" .
			"}");
		$this->assertTrue(true);
	}

	public function nonKeywordsProvider() : array<array<string>> {
		return [
			['on'],
			['fragment'],
			['query'],
			['mutation'],
			['subscription'],
			['true'],
			['false'],
		];
	}

	/**
	 * @test
	 */
	public function parsesAnonymousMutationOperations() : void {
		$this->parse('mutation {
			mutationField
		}');
		$this->assertTrue(true);
	}

	/**
	 * @test
	 */
	public function parsesAnonymousSubscriptionOperations() : void {
		$this->parse('subscription {
			subscriptionField
		}');
		$this->assertTrue(true);
	}

	/**
	 * @test
	 */
	public function parsesNamedMutationOperations() : void {
		$this->parse('mutation Foo {
			mutationField
		}');
		$this->assertTrue(true);
	}

	/**
	 * @test
	 */
	public function parsesNamedSubscriptionOperations() : void {
		$this->parse('subscription Foo {
			subscriptionField
		}');
		$this->assertTrue(true);
	}

	/**
	 * @test
	 */
	public function parseCreatesAST() : void {
		$sourceText =
			"{\n" .
			"  node(id: 4) {\n" .
			"    id,\n" .
			"    name\n" .
			"  }\n" .
			"}\n";
		$source = new Source($sourceText);
		$parser = new Parser($source);

		$doc = $parser->parse();

		$expected = new AST\Document(
			new Location(0, 41, $source),
			ImmVector {
				new AST\OperationDefinition(
					new Location(0, 40, $source),
					AST\Operation::QUERY,
					new AST\SelectionSet(
						new Location(0, 40, $source),
						ImmVector {
							new AST\Field(
								new Location(4, 38, $source),
								new AST\Name(
									new Location(4, 8, $source),
									'node'
								),
								null,
								ImmVector {
									new AST\Argument(
										new Location(9, 14, $source),
										new AST\Name(
											new Location(9, 11, $source),
											'id'
										),
										new AST\IntValue(
											new Location(13, 14, $source),
											'4'
										)
									)
								},
								ImmVector { },
								new AST\SelectionSet(
									new Location(16, 38, $source),
									ImmVector {
										new AST\Field(
											new Location(22, 24, $source),
											new AST\Name(
												new Location(22, 24, $source),
												'id'
											),
											null,
											ImmVector { },
											ImmVector { },
											null
										),
										new AST\Field(
											new Location(30, 34, $source),
											new AST\Name(
												new Location(30, 34, $source),
												'name'
											),
											null,
											ImmVector { },
											ImmVector { },
											null
										)
									}
								)
							)
						}
					),
					null,
					null,
					ImmVector {}
				)
			}
		);

		$this->assertEquals($expected, $doc);
	}
}
