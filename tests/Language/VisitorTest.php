<?hh // strict

namespace GraphQL\Tests\Language;

use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Language\Visitor;
use GraphQL\Language\VisitResult;
use GraphQL\Language\AST;
use PHPUnit_Framework_TestCase;
use HH\ImmVector;

class VisitorTest extends PHPUnit_Framework_TestCase {
	private function parse(string $source) : AST\Document {
		$parser = new Parser(new Source($source), true, false); // no location

		return $parser->parse();
	}

	/**
	 * @test
	 */
	public function returnsUntypedDocumentOnUntypedChanges() : void {
		$ast = $this->parse('{ a, b, c { a, b, c } }');
		$editedAst = Visitor::visit($ast, [
			'enter' => $node ==> {
				if ($node->getKind() === 'Name') {
					return $node->getValue();
				}

				return $node;
			}
		]);

		$this->assertInstanceOf(AST\Untyped\Document::class, $editedAst);
	}
	/**
	 * @test
	 */
	public function returnsSameASTWhenNoEditsOccour() : void {
		$ast = $this->parse('{ a, b, c { a, b, c } }');
		$editedAst = Visitor::visit($ast, [
			'enter' => $node ==> $node,
			'leave' => $node ==> $node,
		]);

		$this->assertSame($ast, $editedAst);
	}
	/**
	 * @test
	 */
	public function allowsForEditingOnEnter() : void {
		$ast =
			$this->parse('{ a, b, c { a, b, c } }');
		$expectedEditedAst =
			$this->parse('{ a,    c { a,    c } }');

		$editedAst = Visitor::visit($ast, [
			'enter' => $node ==> {
				if ($node instanceof AST\Field && $node->getName()->getValue() === 'b'){
					return null;
				}

				return $node;
			}
		]);

		$this->assertNotSame($ast, $editedAst);
		$this->assertEquals($expectedEditedAst, $editedAst);
	}

	/**
	 * @test
	 */
	public function allowsForEditingOnLeave() : void {
		$ast =
			$this->parse('{ a, b, c { a, b, c } }');
		$expectedEditedAst =
			$this->parse('{ a,    c { a,    c } }');

		$editedAst = Visitor::visit($ast, [
			'leave' => $node ==> {
				if ($node instanceof AST\Field && $node->getName()->getValue() === 'b'){
					return null;
				}

				return $node;
			}
		]);

		$this->assertNotSame($ast, $editedAst);
		$this->assertEquals($expectedEditedAst, $editedAst);
	}

	/**
	 * @test
	 */
	public function visitsEditedNode() : void {
		$addedField = new AST\Field(null, new AST\Name(null, '__typename'), null, null, null, null);


		$visitCollector = Vector { };

		$ast = $this->parse('{ a { x } }');

		Visitor::visit($ast, [
			'enter' => $node ==> {
				if ($node instanceof AST\Field && $node->getName()->getValue() === 'a') {
					return new AST\Field(
						null,
						new AST\Name(null, 'c'),
						null,
						null,
						null,
						new AST\SelectionSet(null,
							(Vector { $addedField })->addAll($node->getSelectionSet()->getSelections())->toImmVector()
						)
					);
				}

				if ($node === $addedField) {
					$visitCollector->add(true);
				}

				return $node;
			}
		]);

		$this->assertEquals(1, $visitCollector->count());
	}

	/**
	 * @test
	 */
	public function allowsSkippingASubTree() : void {
		$visited = Vector {};
		$ast = $this->parse('{ a, b { x }, c }');

		$getValue = (AST\Node $node) : ?string ==> {
			if ($node instanceof AST\Name) {
				return $node->getValue();
			}

			return null;
		};

		Visitor::visit($ast, [
			'enter' => $node ==> {
				$visited->add(['enter', $node->getKind(), $getValue($node)]);
				if ($node instanceof AST\Field && $node->getName()->getValue() === 'b') {
					return VisitResult::$SKIP;
				}

				return $node;
			},
			'leave' => $node ==> {
				$visited->add(['leave', $node->getKind(), $getValue($node)]);
				return $node;
			}
		]);

		$expected = Vector {
			['enter', 'Document', null],
			['enter', 'OperationDefinition', null],
			['enter', 'SelectionSet', null],
			['enter', 'Field', null],
			['enter', 'Name', 'a'],
			['leave', 'Name', 'a'],
			['leave', 'Field', null],
			['enter', 'Field', null],
			['enter', 'Field', null],
			['enter', 'Name', 'c'],
			['leave', 'Name', 'c'],
			['leave', 'Field', null],
			['leave', 'SelectionSet', null],
			['leave', 'OperationDefinition', null],
			['leave', 'Document', null],
		};

		$this->assertEquals($expected, $visited);
	}

	/**
	 * @test
	 */
	public function allowsEarlyExitWhileVisiting() : void {
		$visited = Vector { };

		$ast = $this->parse('{ a, b { x }, c }');

		$getValue = (AST\Node $node) : ?string ==> {
			if ($node instanceof AST\Name) {
				return $node->getValue();
			}

			return null;
		};

		Visitor::visit($ast, [
			'enter' => $node ==> {
				$visited->add(['enter', $node->getKind(), $getValue($node)]);

				if ($node instanceof AST\Name && $node->getValue() === 'x') {
					return VisitResult::$BREAK;
				}

				return $node;
			},
			'leave' => $node ==> {
				$visited->add(['leave', $node->getKind(), $getValue($node)]);
				return $node;
			}
		]);

		$expected = Vector {
			[ 'enter', 'Document', null ],
			[ 'enter', 'OperationDefinition', null ],
			[ 'enter', 'SelectionSet', null ],
			[ 'enter', 'Field', null ],
			[ 'enter', 'Name', 'a' ],
			[ 'leave', 'Name', 'a' ],
			[ 'leave', 'Field', null ],
			[ 'enter', 'Field', null ],
			[ 'enter', 'Name', 'b' ],
			[ 'leave', 'Name', 'b' ],
			[ 'enter', 'SelectionSet', null ],
			[ 'enter', 'Field', null ],
			[ 'enter', 'Name', 'x' ]
		};

		$this->assertEquals($expected, $visited);
	}

	/**
	 * @test
	 */
	public function allowsEarlyExitWhileLeaving() : void {
		$visited = Vector { };

		$ast = $this->parse('{ a, b { x }, c }');

		$getValue = (AST\Node $node) : ?string ==> {
			if ($node instanceof AST\Name) {
				return $node->getValue();
			}

			return null;
		};

		Visitor::visit($ast, [
			'enter' => $node ==> {
				$visited->add(['enter', $node->getKind(), $getValue($node)]);
				return $node;
			},
			'leave' => $node ==> {
				$visited->add(['leave', $node->getKind(), $getValue($node)]);

				if ($node instanceof AST\Name && $node->getValue() === 'x') {
					return VisitResult::$BREAK;
				}

				return $node;
			}
		]);

		$expected = Vector {
			[ 'enter', 'Document', null ],
			[ 'enter', 'OperationDefinition', null ],
			[ 'enter', 'SelectionSet', null ],
			[ 'enter', 'Field', null ],
			[ 'enter', 'Name', 'a' ],
			[ 'leave', 'Name', 'a' ],
			[ 'leave', 'Field', null ],
			[ 'enter', 'Field', null ],
			[ 'enter', 'Name', 'b' ],
			[ 'leave', 'Name', 'b' ],
			[ 'enter', 'SelectionSet', null ],
			[ 'enter', 'Field', null ],
			[ 'enter', 'Name', 'x' ],
			[ 'leave', 'Name', 'x' ]
		};

		$this->assertEquals($expected, $visited);
	}

	/**
	 * @test
	 */
	public function allowsANamedFunctionsVisitorAPI() : void {
		$visited = Vector { };

		$ast = $this->parse('{ a, b { x }, c }');

		$getValue = (AST\Node $node) : ?string ==> {
			if ($node instanceof AST\Name) {
				return $node->getValue();
			}

			return null;
		};

		Visitor::visit($ast, [
			'Name' => $node ==> {
				$visited->add(['enter', $node->getKind(), $getValue($node)]);
				return $node;
			},
			'SelectionSet' => [
				'enter' => $node ==> {
					$visited->add(['enter', $node->getKind(), $getValue($node)]);
					return $node;
				},
				'leave' => $node ==> {
					$visited->add(['leave', $node->getKind(), $getValue($node)]);
					return $node;
				},
			],
		]);

		$expected = Vector {
			[ 'enter', 'SelectionSet', null ],
			[ 'enter', 'Name', 'a' ],
			[ 'enter', 'Name', 'b' ],
			[ 'enter', 'SelectionSet', null ],
			[ 'enter', 'Name', 'x' ],
			[ 'leave', 'SelectionSet', null ],
			[ 'enter', 'Name', 'c' ],
			[ 'leave', 'SelectionSet', null ],
		};

		$this->assertEquals($expected, $visited);
	}

	/**
	 * @test
	 */
	 public function visitsKitchenSink() : void {
		$ast = $this->parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'kitchen-sink.graphql'));

		$visited = Vector { };

		Visitor::visit($ast, [
			'enter' => ($node, $visitor, $key, $parent) ==> {
				$visited->add(['enter', $node->getKind(), $key, $parent instanceof AST\Node ? $parent->getKind() : null]);
				return $node;
			},
			'leave' => ($node, $visitor, $key, $parent) ==> {
				$visited->add(['leave', $node->getKind(), $key, $parent instanceof AST\Node ? $parent->getKind() : null]);
				return $node;
			}
		]);

		$expected = Vector {
			[ 'enter', 'Document', null, null ],
			[ 'enter', 'OperationDefinition', 0, null ],
			[ 'enter', 'Name', 'name', 'OperationDefinition' ],
			[ 'leave', 'Name', 'name', 'OperationDefinition' ],
			[ 'enter', 'VariableDefinition', 0, null ],
			[ 'enter', 'Variable', 'variable', 'VariableDefinition' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'variable', 'VariableDefinition' ],
			[ 'enter', 'NamedType', 'type', 'VariableDefinition' ],
			[ 'enter', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'NamedType', 'type', 'VariableDefinition' ],
			[ 'leave', 'VariableDefinition', 0, null ],
			[ 'enter', 'VariableDefinition', 1, null ],
			[ 'enter', 'Variable', 'variable', 'VariableDefinition' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'variable', 'VariableDefinition' ],
			[ 'enter', 'NamedType', 'type', 'VariableDefinition' ],
			[ 'enter', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'NamedType', 'type', 'VariableDefinition' ],
			[ 'enter', 'EnumValue', 'defaultValue', 'VariableDefinition' ],
			[ 'leave', 'EnumValue', 'defaultValue', 'VariableDefinition' ],
			[ 'leave', 'VariableDefinition', 1, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'OperationDefinition' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'alias', 'Field' ],
			[ 'leave', 'Name', 'alias', 'Field' ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'Argument', 0, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'ListValue', 'value', 'Argument' ],
			[ 'enter', 'IntValue', 0, null ],
			[ 'leave', 'IntValue', 0, null ],
			[ 'enter', 'IntValue', 1, null ],
			[ 'leave', 'IntValue', 1, null ],
			[ 'leave', 'ListValue', 'value', 'Argument' ],
			[ 'leave', 'Argument', 0, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'enter', 'InlineFragment', 1, null ],
			[ 'enter', 'NamedType', 'typeCondition', 'InlineFragment' ],
			[ 'enter', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'NamedType', 'typeCondition', 'InlineFragment' ],
			[ 'enter', 'Directive', 0, null ],
			[ 'enter', 'Name', 'name', 'Directive' ],
			[ 'leave', 'Name', 'name', 'Directive' ],
			[ 'leave', 'Directive', 0, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'InlineFragment' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'enter', 'Field', 1, null ],
			[ 'enter', 'Name', 'alias', 'Field' ],
			[ 'leave', 'Name', 'alias', 'Field' ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'Argument', 0, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'IntValue', 'value', 'Argument' ],
			[ 'leave', 'IntValue', 'value', 'Argument' ],
			[ 'leave', 'Argument', 0, null ],
			[ 'enter', 'Argument', 1, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'Variable', 'value', 'Argument' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'value', 'Argument' ],
			[ 'leave', 'Argument', 1, null ],
			[ 'enter', 'Directive', 0, null ],
			[ 'enter', 'Name', 'name', 'Directive' ],
			[ 'leave', 'Name', 'name', 'Directive' ],
			[ 'enter', 'Argument', 0, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'Variable', 'value', 'Argument' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'value', 'Argument' ],
			[ 'leave', 'Argument', 0, null ],
			[ 'leave', 'Directive', 0, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'enter', 'FragmentSpread', 1, null ],
			[ 'enter', 'Name', 'name', 'FragmentSpread' ],
			[ 'leave', 'Name', 'name', 'FragmentSpread' ],
			[ 'leave', 'FragmentSpread', 1, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 1, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'InlineFragment' ],
			[ 'leave', 'InlineFragment', 1, null ],
			[ 'enter', 'InlineFragment', 2, null ],
			[ 'enter', 'Directive', 0, null ],
			[ 'enter', 'Name', 'name', 'Directive' ],
			[ 'leave', 'Name', 'name', 'Directive' ],
			[ 'enter', 'Argument', 0, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'Variable', 'value', 'Argument' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'value', 'Argument' ],
			[ 'leave', 'Argument', 0, null ],
			[ 'leave', 'Directive', 0, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'InlineFragment' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'InlineFragment' ],
			[ 'leave', 'InlineFragment', 2, null ],
			[ 'enter', 'InlineFragment', 3, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'InlineFragment' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'InlineFragment' ],
			[ 'leave', 'InlineFragment', 3, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'OperationDefinition' ],
			[ 'leave', 'OperationDefinition', 0, null ],
			[ 'enter', 'OperationDefinition', 1, null ],
			[ 'enter', 'Name', 'name', 'OperationDefinition' ],
			[ 'leave', 'Name', 'name', 'OperationDefinition' ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'OperationDefinition' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'Argument', 0, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'IntValue', 'value', 'Argument' ],
			[ 'leave', 'IntValue', 'value', 'Argument' ],
			[ 'leave', 'Argument', 0, null ],
			[ 'enter', 'Directive', 0, null ],
			[ 'enter', 'Name', 'name', 'Directive' ],
			[ 'leave', 'Name', 'name', 'Directive' ],
			[ 'leave', 'Directive', 0, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'OperationDefinition' ],
			[ 'leave', 'OperationDefinition', 1, null ],
			[ 'enter', 'OperationDefinition', 2, null ],
			[ 'enter', 'Name', 'name', 'OperationDefinition' ],
			[ 'leave', 'Name', 'name', 'OperationDefinition' ],
			[ 'enter', 'VariableDefinition', 0, null ],
			[ 'enter', 'Variable', 'variable', 'VariableDefinition' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'variable', 'VariableDefinition' ],
			[ 'enter', 'NamedType', 'type', 'VariableDefinition' ],
			[ 'enter', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'NamedType', 'type', 'VariableDefinition' ],
			[ 'leave', 'VariableDefinition', 0, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'OperationDefinition' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'Argument', 0, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'Variable', 'value', 'Argument' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'value', 'Argument' ],
			[ 'leave', 'Argument', 0, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'enter', 'Field', 1, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 1, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'Field' ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'OperationDefinition' ],
			[ 'leave', 'OperationDefinition', 2, null ],
			[ 'enter', 'FragmentDefinition', 3, null ],
			[ 'enter', 'Name', 'name', 'FragmentDefinition' ],
			[ 'leave', 'Name', 'name', 'FragmentDefinition' ],
			[ 'enter', 'NamedType', 'typeCondition', 'FragmentDefinition' ],
			[ 'enter', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'Name', 'name', 'NamedType' ],
			[ 'leave', 'NamedType', 'typeCondition', 'FragmentDefinition' ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'FragmentDefinition' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'Argument', 0, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'Variable', 'value', 'Argument' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'value', 'Argument' ],
			[ 'leave', 'Argument', 0, null ],
			[ 'enter', 'Argument', 1, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'Variable', 'value', 'Argument' ],
			[ 'enter', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Name', 'name', 'Variable' ],
			[ 'leave', 'Variable', 'value', 'Argument' ],
			[ 'leave', 'Argument', 1, null ],
			[ 'enter', 'Argument', 2, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'ObjectValue', 'value', 'Argument' ],
			[ 'enter', 'ObjectField', 0, null ],
			[ 'enter', 'Name', 'name', 'ObjectField' ],
			[ 'leave', 'Name', 'name', 'ObjectField' ],
			[ 'enter', 'StringValue', 'value', 'ObjectField' ],
			[ 'leave', 'StringValue', 'value', 'ObjectField' ],
			[ 'leave', 'ObjectField', 0, null ],
			[ 'leave', 'ObjectValue', 'value', 'Argument' ],
			[ 'leave', 'Argument', 2, null ],
			[ 'leave', 'Field', 0, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'FragmentDefinition' ],
			[ 'leave', 'FragmentDefinition', 3, null ],
			[ 'enter', 'OperationDefinition', 4, null ],
			[ 'enter', 'SelectionSet', 'selectionSet', 'OperationDefinition' ],
			[ 'enter', 'Field', 0, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'enter', 'Argument', 0, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'BooleanValue', 'value', 'Argument' ],
			[ 'leave', 'BooleanValue', 'value', 'Argument' ],
			[ 'leave', 'Argument', 0, null ],
			[ 'enter', 'Argument', 1, null ],
			[ 'enter', 'Name', 'name', 'Argument' ],
			[ 'leave', 'Name', 'name', 'Argument' ],
			[ 'enter', 'BooleanValue', 'value', 'Argument' ],
			[ 'leave', 'BooleanValue', 'value', 'Argument' ],
			[ 'leave', 'Argument', 1, null ],
			[ 'leave', 'Field', 0, null ],
			[ 'enter', 'Field', 1, null ],
			[ 'enter', 'Name', 'name', 'Field' ],
			[ 'leave', 'Name', 'name', 'Field' ],
			[ 'leave', 'Field', 1, null ],
			[ 'leave', 'SelectionSet', 'selectionSet', 'OperationDefinition' ],
			[ 'leave', 'OperationDefinition', 4, null ],
			[ 'leave', 'Document', null, null ]
		};

		$this->assertEquals($expected, $visited);
	 }
}
