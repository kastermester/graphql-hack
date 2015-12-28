<?hh // strict

namespace GraphQL\Tests\Language;

use GraphQL\Language\AST;
use GraphQL\Language\Location;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use ConstVector;
use PHPUnit_Framework_TestCase;

class SchemaParserTest extends PHPUnit_Framework_TestCase {
	private static function createLocFn(string $body) : (function(int, int) : Location) {
		return (int $start, int $end) ==> new Location($start, $end, new Source($body, 'GraphQL'));
	}

	private static function typeNode(string $name, ?Location $loc) : AST\NamedType {
		return new AST\NamedType($loc, self::nameNode($name, $loc));
	}

	private static function nameNode(string $name, ?Location $loc) : AST\Name {
		return new AST\Name($loc, $name);
	}

	private static function fieldNode(AST\Name $name, AST\TypeNode $type, ?Location $loc) : AST\FieldDefinition {
		return self::fieldNodeWithArgs($name, $type, ImmVector { }, $loc);
	}

	private static function fieldNodeWithArgs(AST\Name $name, AST\TypeNode $type, ConstVector<AST\InputValueDefinition> $args, ?Location $loc) : AST\FieldDefinition {
		return new AST\FieldDefinition($loc, $name, $args, $type);
	}

	private static function enumValueNode(string $name, ?Location $loc) : AST\EnumValueDefinition {
		return new AST\EnumValueDefinition($loc, self::nameNode($name, $loc));
	}

	private static function inputValueNode(AST\Name $name, AST\TypeNode $type, ?AST\Value $defaultValue, ?Location $loc) : AST\InputValueDefinition {
		return new AST\InputValueDefinition($loc, $name, $type, $defaultValue);
	}

	private static function parse(string $body) : AST\Document {
		$parser = new Parser(new Source($body, 'GraphQL'));
		return $parser->parse();
	}
	/**
	 * @test
	 */
	public function simpleType() : void {
		$body = <<<GRAPHQL

type Hello {
  world: String
}
GRAPHQL;
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(1, 31),
			ImmVector {
				new AST\ObjectTypeDefinition(
					$loc(1, 31),
					self::nameNode('Hello', $loc(6, 11)),
					ImmVector {
						self::fieldNode(
							self::nameNode('world', $loc(16, 21)),
							self::typeNode('String', $loc(23, 29)),
							$loc(16, 29),
						),
					},
					ImmVector { }
				),
			},
		);

		$this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 */
	 public function simpleExtension() : void {
		 $body = <<<GRAPHQL

extend type Hello {
  world: String
}
GRAPHQL;
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(1, 38),
			ImmVector {
				new AST\TypeExtensionDefinition(
					$loc(1, 38),
					new AST\ObjectTypeDefinition(
						$loc(8, 38),
						self::nameNode('Hello', $loc(13, 18)),
						ImmVector {
							self::fieldNode(
								self::nameNode('world', $loc(23, 28)),
								self::typeNode('String', $loc(30, 36)),
								$loc(23, 36),
							)
						},
						ImmVector { },
					)
				)
			}
		);

		$this->assertEquals($expected, $doc);
	 }

	 /**
	  * @test
	  */
	 public function simpleNonNullType() : void {
		 $body = <<<GRAPHQL

type Hello {
  world: String!
}
GRAPHQL;
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(1, 32),
			ImmVector {
				new AST\ObjectTypeDefinition(
					$loc(1, 32),
					self::nameNode('Hello', $loc(6, 11)),
					ImmVector {
						self::fieldNode(
							self::nameNode('world', $loc(16, 21)),
							new AST\NonNullType(
								$loc(23, 30),
								self::typeNode('String', $loc(23, 29)),
							),
							$loc(16, 30),
						)
					},
					ImmVector { },
				)
			}
		);

		$this->assertEquals($expected, $doc);
	 }

	 /**
	  * @test
	  */
	 public function simpleTypeInheritingMultipleInterfaces() : void {
		$body = 'type Hello implements Wo, rld { }';
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(0, 33),
			ImmVector {
				new AST\ObjectTypeDefinition(
					$loc(0, 33),
					self::nameNode('Hello', $loc(5, 10)),
					ImmVector { },
					ImmVector {
						self::typeNode('Wo', $loc(22, 24)),
						self::typeNode('rld', $loc(26, 29)),
					},
				)
			}
		);

		$this->assertEquals($expected, $doc);
	 }

	 /**
	  * @test
	  */
	 public function simpleValueEnum() : void {
		$body = 'enum Hello { WORLD }';
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(0, 20),
			ImmVector {
				new AST\EnumTypeDefinition(
					$loc(0, 20),
					self::nameNode('Hello', $loc(5, 10)),
					ImmVector {
						self::enumValueNode('WORLD', $loc(13, 18)),
					},
				)
			}
		);

		$this->assertEquals($expected, $doc);
	 }

	 /**
	  * @test
	  */
	 public function doubleValueEnum() : void {
		$body = 'enum Hello { WO, RLD }';
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(0, 22),
			ImmVector {
				new AST\EnumTypeDefinition(
					$loc(0, 22),
					self::nameNode('Hello', $loc(5, 10)),
					ImmVector {
						self::enumValueNode('WO', $loc(13, 15)),
						self::enumValueNode('RLD', $loc(17, 20)),
					},
				)
			}
		);

		$this->assertEquals($expected, $doc);
	 }

 	/**
 	 * @test
 	 */
 	public function simpleInterface() : void {
 		$body = <<<GRAPHQL

interface Hello {
  world: String
}
GRAPHQL;
 		$doc = self::parse($body);
 		$loc = self::createLocFn($body);
 		$expected = new AST\Document(
 			$loc(1, 36),
 			ImmVector {
 				new AST\InterfaceTypeDefinition(
 					$loc(1, 36),
 					self::nameNode('Hello', $loc(11, 16)),
 					ImmVector {
 						self::fieldNode(
							self::nameNode('world', $loc(21, 26)),
							self::typeNode('String', $loc(28, 34)),
							$loc(21, 34),
						),
 					},
 				),
 			}
 		);

 		$this->assertEquals($expected, $doc);
 	}

	/**
	 * @test
	 */
	public function simpleFieldWithArg() : void {
		$body = <<<GRAPHQL

type Hello {
  world(flag: Boolean): String
}
GRAPHQL;
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(1, 46),
			ImmVector {
				new AST\ObjectTypeDefinition(
					$loc(1, 46),
					self::nameNode('Hello', $loc(6, 11)),
					ImmVector {
						self::fieldNodeWithArgs(
							self::nameNode('world', $loc(16, 21)),
							self::typeNode('String', $loc(38, 44)),
							ImmVector {
								self::inputValueNode(
									self::nameNode('flag', $loc(22, 26)),
									self::typeNode('Boolean', $loc(28, 35)),
									null,
									$loc(22, 35),
								),
							},
							$loc(16, 44),
						),
					},
					ImmVector { },
				)
			}
		);

	   $this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 */
	public function simpleFieldWithArgWithDefaultValue() : void {
		$body = <<<GRAPHQL

type Hello {
  world(flag: Boolean = true): String
}
GRAPHQL;
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(1, 53),
			ImmVector {
				new AST\ObjectTypeDefinition(
					$loc(1, 53),
					self::nameNode('Hello', $loc(6, 11)),
					ImmVector {
						self::fieldNodeWithArgs(
							self::nameNode('world', $loc(16, 21)),
							self::typeNode('String', $loc(45, 51)),
							ImmVector {
								self::inputValueNode(
									self::nameNode('flag', $loc(22, 26)),
									self::typeNode('Boolean', $loc(28, 35)),
									new AST\BooleanValue(
										$loc(38, 42),
										true
									),
									$loc(22, 42),
								),
							},
							$loc(16, 51),
						),
					},
					ImmVector { },
				)
			}
		);

		$this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 */
	public function simpleFieldWithListArg() : void {
		$body = <<<GRAPHQL

type Hello {
  world(things: [String]): String
}
GRAPHQL;
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(1, 49),
			ImmVector {
				new AST\ObjectTypeDefinition(
					$loc(1, 49),
					self::nameNode('Hello', $loc(6, 11)),
					ImmVector {
						self::fieldNodeWithArgs(
							self::nameNode('world', $loc(16, 21)),
							self::typeNode('String', $loc(41, 47)),
							ImmVector {
								self::inputValueNode(
									self::nameNode('things', $loc(22, 28)),
									new AST\ListType(
										$loc(30, 38),
										self::typeNode('String', $loc(31, 37)),
									),
									null,
									$loc(22, 38),
								),
							},
							$loc(16, 47),
						),
					},
					ImmVector { },
				)
			}
		);

		$this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 */
	public function simpleFieldWithTwoArgs() : void {
		$body = <<<GRAPHQL

type Hello {
  world(argOne: Boolean, argTwo: Int): String
}
GRAPHQL;
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(1, 61),
			ImmVector {
				new AST\ObjectTypeDefinition(
					$loc(1, 61),
					self::nameNode('Hello', $loc(6, 11)),
					ImmVector {
						self::fieldNodeWithArgs(
							self::nameNode('world', $loc(16, 21)),
							self::typeNode('String', $loc(53, 59)),
							ImmVector {
								self::inputValueNode(
									self::nameNode('argOne', $loc(22, 28)),
									self::typeNode('Boolean', $loc(30, 37)),
									null,
									$loc(22, 37),
								),
								self::inputValueNode(
									self::nameNode('argTwo', $loc(39, 45)),
									self::typeNode('Int', $loc(47, 50)),
									null,
									$loc(39, 50),
								),
							},
							$loc(16, 59),
						),
					},
					ImmVector { },
				)
			}
		);

		$this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 */
	public function simpleUnion() : void {
		$body = 'union Hello = World';
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(0, 19),
			ImmVector {
				new AST\UnionTypeDefinition(
					$loc(0, 19),
					self::nameNode('Hello', $loc(6, 11)),
					ImmVector {
						self::typeNode('World', $loc(14, 19))
					},
				),
			}
		);

		$this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 */
	public function simpleUnionWithTwoTypes() : void {
		$body = 'union Hello = Wo | Rld';
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(0, 22),
			ImmVector {
				new AST\UnionTypeDefinition(
					$loc(0, 22),
					self::nameNode('Hello', $loc(6, 11)),
					ImmVector {
						self::typeNode('Wo', $loc(14, 16)),
						self::typeNode('Rld', $loc(19, 22)),
					},
				),
			}
		);

		$this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 */
	public function simpleScalar() : void {
		$body = 'scalar Hello';
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(0, 12),
			ImmVector {
				new AST\ScalarTypeDefinition(
					$loc(0, 12),
					self::nameNode('Hello', $loc(7, 12)),
				),
			}
		);

		$this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 */
	public function simpleInputObject() : void {
		$body = <<<GRAPHQL

input Hello {
  world: String
}
GRAPHQL;
		$doc = self::parse($body);
		$loc = self::createLocFn($body);
		$expected = new AST\Document(
			$loc(1, 32),
			ImmVector {
				new AST\InputObjectTypeDefinition(
					$loc(1, 32),
					self::nameNode('Hello', $loc(7, 12)),
					ImmVector {
						self::inputValueNode(
							self::nameNode('world', $loc(17, 22)),
							self::typeNode('String', $loc(24, 30)),
							null,
							$loc(17, 30),
						),
					}
				),
			}
		);

		$this->assertEquals($expected, $doc);
	}

	/**
	 * @test
	 * @expectedException GraphQL\SyntaxException
	 * @expectedExceptionMessageRegExp /^Syntax Error GraphQL \(3:8\) Expected :, found \(/
	 */
	public function simpleInputObjectwithArgsShouldFail() : void {
		$body = <<<GRAPHQL

input Hello {
  world(foo: Int): String
}
GRAPHQL;

		self::parse($body);
	}

	 /**
	 * @test
	 */
	 public function parsesKitchenSink() : void {
		 self::parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'schema-kitchen-sink.graphql'));
		 // No exception is good business here
		 $this->assertTrue(true);
	 }
}
