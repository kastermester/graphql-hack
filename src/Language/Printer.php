<?hh

namespace GraphQL\Language;

use ConstVector;
use GraphQL\Assert;
use HH\Vector;
use HH\ImmVector;

final class Printer {
	private function __construct() {}
	public static function print(AST\Node $node) : string {
		$printed = Visitor::visit($node, [
			'leave' => [
				'Name' => $node ==> $node->getValue(),
				'Variable' => $node ==> '$' . $node->getName(),

				'Document' => $node ==> self::join($node->getDefinitions(), "\n\n") . "\n",

				'OperationDefinition' => $node ==> {
					$op = (string)$node->getOperation();
					$name = $node->getName();
					$defs = self::wrap(
						'(',
						self::join($node->getVariableDefinitions(), ', '),
						')'
					);
					$directives = self::join($node->getDirectives(), ' ');
					$selectionSet = $node->getSelectionSet();

					return
						is_null($name) ?
						$selectionSet :
						self::join(ImmVector {
							$op,
							self::join(ImmVector {
								$name,
								$defs
							}),
							$directives,
							$selectionSet
						}, ' ');
				},

				'VariableDefinition' => $node ==> {
					return
						$node->getVariable() .
						': ' .
						$node->getType() .
						self::wrap(' = ', $node->getDefaultValue());
				},

				'SelectionSet' => $node ==> self::block($node->getSelections()),

				'Field' => $node ==> {
					$alias = $node->getAlias();
					$name = $node->getName();
					$args = $node->getArguments();
					$directives = $node->getDirectives();
					$selectionSet = $node->getSelectionSet();

					$alias = self::wrap('', $alias, ': ');
					$args = self::wrap('(', self::join($args, ', '), ')');

					return self::join(ImmVector {
						$alias . $name . $args,
						self::join($directives, ' '),
						$selectionSet
					}, ' ');
				},

				'Argument' => $node ==> $node->getName() . ': ' . $node->getValue(),

				'FragmentSpread' => $node ==> {
					return
						'...' .
						$node->getName() .
						self::wrap(
							' ',
							self::join($node->getDirectives()),
							' '
						);
				},

				'InlineFragment' => $node ==> {
					$typeCondition = $node->getTypeCondition();
					$directives = $node->getDirectives();
					$selectionSet = $node->getSelectionSet();

					return self::join(ImmVector {
						'...',
						self::wrap('on ', $typeCondition),
						self::join($directives, ' '),
						$selectionSet,
					}, ' ');
				},

				'FragmentDefinition' => $node ==> {
					$name = $node->getName();
					$typeCondition = $node->getTypeCondition();
					$directives = $node->getDirectives();
					$selectionSet = $node->getSelectionSet();

					return
						"fragment $name on $typeCondition " .
						self::wrap('', self::join($directives, ' '), ' ') .
						$selectionSet;
				},

				'IntValue' => $node ==> $node->getValue(),
				'FloatValue' => $node ==> $node->getValue(),
				'StringValue' => $node ==> json_encode($node->getValue()),
				'BooleanValue' => $node ==> $node->getValue() ? 'true' : 'false',
				'EnumValue' => $node ==> $node->getValue(),
				'ListValue' => $node ==> '[' . self::join($node->getValues(), ', ') . ']',
				'ObjectValue' => $node ==> '{' . self::join($node->getFields(), ', ') . '}',
				'ObjectField' => $node ==> $node->getName() . ': ' . $node->getValue(),

				'Directive' => $node ==> {
					$name = $node->getName();
					$args = self::wrap('(', self::join($node->getArguments(), ', '), ')');

					return '@' . $name . $args;
				},

				'NamedType' => $node ==> $node->getName(),
				'ListType' => $node ==> '[' . $node->getType() . ']',
				'NonNullType' => $node ==> $node->getType() . '!',

				'ObjectTypeDefinition' => $node ==> {
					$name = $node->getName();
					$interfaces = $node->getInterfaces();
					$fields = $node->getFields();

					$implements = self::wrap(
						'implements ',
						self::join($interfaces, ', '),
						' '
					);
					$blockFields = self::block($fields);

					return
						'type ' .
						$name .
						' ' .
						$implements .
						$blockFields;
				},

				'FieldDefinition' => $node ==> {
					$name = $node->getName();
					$arguments = $node->getArguments();
					$type = $node->getType();

					$args = self::wrap('(', self::join($arguments, ', '), ')');

					return
						$name .
						$args .
						': ' .
						$type;
				},

				'InputValueDefinition' => $node ==> {
					$name = $node->getName();
					$type = $node->getType();
					$defaultValue = $node->getDefaultValue();

					return
						$name .
						': ' .
						$type .
						self::wrap(' = ', $defaultValue);
				},

				'InterfaceTypeDefinition' => $node ==> {
					return
						'interface ' .
						$node->getName() .
						' ' .
						self::block($node->getFields());
				},

				'UnionTypeDefinition' => $node ==> {
					return
						'union ' .
						$node->getName() .
						' = ' .
						self::join($node->getTypes(), ' | ');
				},

				'ScalarTypeDefinition' => $node ==> 'scalar ' . $node->getName(),

				'EnumTypeDefinition' => $node ==> {
					return
						'enum ' .
						$node->getName() .
						' ' .
						self::block($node->getValues());
				},

				'EnumValueDefinition' => $node ==> $node->getName(),

				'InputObjectTypeDefinition' => $node ==> {
					return
						'input ' .
						$node->getName() .
						' ' .
						self::block($node->getFields());
				},

				'TypeExtensionDefinition' => $node ==> {
					return 'extend ' . $node->getDefinition();
				},
			],
		]);

		return Assert::isString($printed);
	}

	private static function join(?ConstVector<?string> $maybeVector, string $separator = '') : string {
		if (is_null($maybeVector)) {
			return '';
		}
		$vector = new Vector($maybeVector->filter($x ==> !is_null($x) && strlen($x) > 0));

		return implode($separator, $vector);
	}

	private static function block(?ConstVector<string> $maybeVector) {
		return
			self::length($maybeVector) > 0 ?
			self::indent("{\n" . self::join($maybeVector, "\n")) . "\n}" :
			'';
	}

	private static function wrap(string $start, ?string $maybeString, string $end = '') : string {
		return is_null($maybeString) || strlen($maybeString) === 0 ? '' : ($start . $maybeString . $end);
	}

	private static function indent(?string $maybeString) : string {
		if (is_null($maybeString) || strlen($maybeString) === 0) {
			return '';
		}

		return str_replace("\n", "\n  ", $maybeString);
	}

	private static function length(?ConstVector<?string> $maybeVector) : int {
		return is_null($maybeVector) ? 0 : $maybeVector->count();
	}
}
