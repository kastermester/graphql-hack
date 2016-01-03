<?hh

namespace GraphQL\Language;

use ConstVector;
use ConstMap;
use GraphQL\Assert;
use HH\ImmMap;
use HH\ImmVector;
use HH\Vector;
use HH\Map;
use ReflectionClass;

// This is quite ugly code. Unfortunately the original design in graphql-js
// does not allow me a whole lot of flexibility in terms of designing a more
// type safe API.
//
// I might try to create a more object oriented API later on,
// where focus on type safety will be #1 priority.
// However I fear that this implementation might actually be faster,
// than an implementation based on recursion (which would be my plan).

final class VisitResult {
	private function __construct(){}

	public static function init() : void {
		if (is_null(self::$SKIP)) {
			self::$SKIP = new VisitResult();
			self::$BREAK = new VisitResult();
		}
	}
	// Don't reassign these, please
	public static ?VisitResult $SKIP = null;
	public static ?VisitResult $BREAK = null;
}
type QueryDocumentKeys = ConstMap<string, ConstVector<string>>;
type ASTConstructorArguments = ConstMap<string, ConstVector<string>>;

final class Visitor {
	private function __construct(){}

	private static ASTConstructorArguments $constructorArguments = ImmMap {
		"Name" => ImmVector { 'location', 'value' },
		"Document" => ImmVector { 'location', 'definitions' },
	    "OperationDefinition" => ImmVector { 'location', 'operation', 'selectionSet', 'name', 'variableDefinitions', 'directives' },
	    "VariableDefinition" => ImmVector { 'location', 'variable', 'type', 'defaultValue' },
	    "Variable" => ImmVector { 'location', 'name' },
	    "SelectionSet" => ImmVector { 'location', 'selections' },
	    "Field" => ImmVector { 'location', 'name', 'alias', 'arguments', 'directives', 'selectionSet' },
	    "Argument" => ImmVector { 'location', 'name', 'value' },

	    "FragmentSpread" => ImmVector { 'location', 'name', 'directives' },
	    "InlineFragment" => ImmVector { 'location',  'selectionSet', 'typeCondition', 'directives' },
	    "FragmentDefinition" => ImmVector { 'location', 'name', 'selectionSet', 'typeCondition', 'directives' },

	    "IntValue" => ImmVector { 'location', 'value' },
	    "FloatValue" => ImmVector { 'location', 'value' },
	    "StringValue" => ImmVector { 'location', 'value' },
	    "BooleanValue" => ImmVector { 'location', 'value' },
	    "EnumValue" => ImmVector { 'location', 'value' },
	    "ListValue" => ImmVector { 'location', 'values' },
	    "ObjectValue" => ImmVector { 'location', 'fields' },
	    "ObjectField" => ImmVector { 'location', 'name', 'value' },

	    "Directive" => ImmVector { 'location', 'name', 'arguments' },

	    "NamedType" => ImmVector { 'location', 'name' },
	    "ListType" => ImmVector { 'location', 'type' },
	    "NonNullType" => ImmVector { 'location', 'type' },

	    "ObjectTypeDefinition" => ImmVector { 'location', 'name', 'fields', 'interfaces' },
	    "FieldDefinition" => ImmVector { 'location', 'name', 'arguments', 'type' },
	    "InputValueDefinition" => ImmVector { 'location', 'name', 'type', 'defaultValue' },
	    "InterfaceTypeDefinition" => ImmVector { 'location', 'name', 'fields' },
	    "UnionTypeDefinition" => ImmVector { 'location', 'name', 'types' },
	    "ScalarTypeDefinition" => ImmVector { 'location', 'name' },
	    "EnumTypeDefinition" => ImmVector { 'location', 'name', 'values' },
	    "EnumValueDefinition" => ImmVector { 'location', 'name' },
	    "InputObjectTypeDefinition" => ImmVector { 'location', 'name', 'fields' },
	    "TypeExtensionDefinition" => ImmVector { 'location', 'definition' },
	};

	private static QueryDocumentKeys $queryDocumentKeys = ImmMap {
		'Name' => ImmVector {},

		'Document' => ImmVector { 'definitions' },
		'OperationDefinition' => ImmVector { 'name', 'variableDefinitions', 'directives', 'selectionSet' },
		'VariableDefinition' => ImmVector { 'variable', 'type', 'defaultValue' },
		'Variable' => ImmVector { 'name' },
		'SelectionSet' => ImmVector { 'selections' },
		'Field' => ImmVector { 'alias', 'name', 'arguments', 'directives', 'selectionSet' },
		'Argument' => ImmVector { 'name', 'value' },

		'FragmentSpread' => ImmVector { 'name', 'directives' },
		'InlineFragment' => ImmVector { 'typeCondition', 'directives', 'selectionSet' },
		'FragmentDefinition' => ImmVector { 'name', 'typeCondition', 'directives', 'selectionSet' },

		'IntValue' => ImmVector {},
		'FloatValue' => ImmVector {},
		'StringValue' => ImmVector {},
		'BooleanValue' => ImmVector {},
		'EnumValue' => ImmVector {},
		'ListValue' => ImmVector { 'values' },
		'ObjectValue' => ImmVector { 'fields' },
		'ObjectField' => ImmVector { 'name', 'value' },

		'Directive' => ImmVector { 'name', 'arguments' },

		'NamedType' => ImmVector { 'name' },
		'ListType' => ImmVector { 'type' },
		'NonNullType' => ImmVector { 'type' },

		'ObjectTypeDefinition' => ImmVector { 'name', 'interfaces', 'fields' },
		'FieldDefinition' => ImmVector { 'name', 'arguments', 'type' },
		'InputValueDefinition' => ImmVector { 'name', 'type', 'defaultValue' },
		'InterfaceTypeDefinition' => ImmVector { 'name', 'fields' },
		'UnionTypeDefinition' => ImmVector { 'name', 'types' },
		'ScalarTypeDefinition' => ImmVector { 'name' },
		'EnumTypeDefinition' => ImmVector { 'name', 'values' },
		'EnumValueDefinition' => ImmVector { 'name' },
		'InputObjectTypeDefinition' => ImmVector { 'name', 'fields' },
		'TypeExtensionDefinition' => ImmVector { 'definition' },
	};

	public static function visit(mixed $root, array $visitor, ?QueryDocumentKeys $keyMap = null) : mixed {
		VisitResult::init();
		$visitorKeys = !is_null($keyMap) ? $keyMap : self::$queryDocumentKeys;

		$stack = null;
		$inArray = $root instanceof ConstVector;
		$keys = Vector { $root };
		$index = -1;
		$edits = [];
		$parent = null;
		$path = Vector { };
		$ancestors = Vector { };
		$typed = true;
		$newRoot = $root;

		do {
			$index = Assert::isInt($index+1);
			$isLeaving = $index === $keys->count();
			$key = null;
			$node = null;
			$isEdited = $isLeaving && count($edits) !== 0;

			if ($isLeaving) {
				$key = $ancestors->count() === 0 ? null : $path->pop();
				$node = $parent;
				$parent = $ancestors->count() > 0 ? $ancestors->pop() : null;
				if ($isEdited) {
					if ($inArray) {
						$newNode = Assert::isInstanceOf($node, ConstVector::class)->toVector();
						// Reassign the new values in the respective keys
						foreach ($edits as $editKey => $value) {
							$editKey = Assert::isInt($editKey);
							$newNode[$editKey] = $value;
						}

						// Remove null values and convert back to immutable vector
						$node = $newNode->filter($v ==> $v !== null)->toImmVector();
					} else {
						$node = Assert::isInstanceOf($node, AST\Node::class);
						$kind = $node->getKind();
						$params = [];
						$docKeys = self::$constructorArguments[$kind];
						$valuesEdited = 0;
						foreach ($docKeys as $docKey) {
							if (array_key_exists($docKey, $edits)) {
								$editedValue = $edits[$docKey];
								$params[] = $editedValue;
							} else {
								$params[] = self::resolveNodeProperty($node, $docKey);
							}
						}
						$reflectionClass = self::getReflectionClass($kind, $typed);

						$node = Assert::isInstanceOf($reflectionClass->newInstanceArgs($params), AST\Node::class);
					}
				}
				if (is_null($stack)) {
					throw new \Exception("Expected stack to be non-null");
				}
				$index = $stack['index'];
				$keys = $stack['keys'];
				$edits = $stack['edits'];
				$inArray = $stack['inArray'];
				// We're typed if up the tree is typed
				// and was typed down the tree at this point.
				// In theory this means a tree that has become untyped
				// will always be untyped at the root, but may have branches
				// that are typed
				$typed = $stack['typed'] && $typed;
				$stack = $stack['prev'];
			} else {
				$key = !is_null($parent) ? ($inArray ? $index : $keys[$index]) : null;
				$node = null;
				if (is_null($parent)) {
					$node = $newRoot;
				} else {
					if ($parent instanceof ConstVector) {
						invariant(is_int($key), 'Key must be integer');
						$node = Assert::isInstanceOf($parent, ConstVector::class)[$key];
					} else {
						invariant(is_string($key), 'Key must be a string');
						$node = self::resolveNodeProperty(Assert::isInstanceOf($parent, AST\Node::class), $key);
					}
				}
				if ($node === null || is_scalar($node)) { // Some keys are added which contains scalar properties
					continue;
				}

				if (!is_null($parent)) {
					$path->add($key);
				}
			}

			$result = $node;
			$origNode = $node;
			if (!($node instanceof ConstVector)) {
				if (!($node instanceof AST\Node)) {
					throw new \Exception('Invalid AST Node: ' . var_export($node, true));
				}
				/* HH_FIXME[ 4062] For some reason $node is not being refined to AST\Node? */
				$kind = $node->getKind();
				$visitFn = self::getVisitFn($visitor, $kind, $isLeaving);

				if (is_callable($visitFn)) {
					/* HH_FIXME[4009] It is a callable, we have just tested for it */
					$result = call_user_func($visitFn, $node, $visitor, $key, $parent, $path, $ancestors);

					if ($result === VisitResult::$BREAK) {
						break;
					}

					if ($result === VisitResult::$SKIP) {
						if (!$isLeaving) {
							$path->pop();
							continue;
						}
					} else if ($result !== $node) {
						$edits[$key] = $result;
						if (
							$node instanceof AST\Node &&
							!is_null($result) &&
							(
								!($result instanceof AST\Node) ||
								$result instanceof AST\Untyped\Node
							)
						) {
							// We went from an AST node to a non typed node
							// and non null value
							// when converting the AST from now on
							// switch to untyped mode
							$typed = false;
						}
						if (!$isLeaving) {
							if ($result instanceof AST\Node) {
								$node = $result;
							} else {
								if ($path->count() > 0) {
									$path->pop();
								}
								continue;
							}
						}
					}
				}
			}

			if ($result === $origNode && $isEdited) {
				$edits[$key] = $node;
			}

			if (!$isLeaving) {
				$stack = [
					'inArray' => $inArray,
					'index' => $index,
					'keys' => $keys,
					'edits' => $edits,
					'prev' => $stack,
					'typed' => $typed,
				];
				$inArray = $node instanceof ConstVector;
				if ($inArray) {
					$node = Assert::isInstanceOf($node, ConstVector::class);
					$keys = $node->keys();
				} else {
					if ($node instanceof AST\Node) {
						$node = Assert::isInstanceOf($node, AST\Node::class);
						$kind = $node->getKind();
						$keys = $visitorKeys->contains($kind) ? $visitorKeys[$kind] : Vector { };
					} else {
						$keys = Vector { };
					}
				}
				$index = -1;
				$edits = [];
				if (!is_null($parent)) {
					$ancestors->add($parent);
				}
				$parent = $node;
				// The tree is typed from this point on
				// untill proven otherwise
				$typed = true;
			}
		} while ($stack !== null);

		$edits = Assert::isNonNull($edits);
		if (count($edits) !== 0) {
			reset($edits);
			$newRoot = $edits[key($edits)];
		}

		return $newRoot;
	}

	private static function resolveNodeProperty(AST\Node $parent, string $key) : mixed {
		$method = 'get' . ucfirst($key);

		return $parent->$method();
	}

	// TODO: Implement visitInParallel and visitWithTypeInfo once schema
	// definition is done

	// return is ?callable but Hack disallows it
	private static function getVisitFn(array $visitor, string $kind, bool $isLeaving) : mixed {
		if (array_key_exists($kind, $visitor)) {
			$kindVisitor = $visitor[$kind];
			if (!$isLeaving && is_callable($kindVisitor)) {
				// [ 'Kind' => function ... ]
				return $kindVisitor;
			}
			$enterLeave = $isLeaving ? 'leave' : 'enter';
			if (is_array($kindVisitor) && array_key_exists($enterLeave, $kindVisitor)) {
				$kindSpecificVisitor = $kindVisitor[$enterLeave];
				if (is_callable($kindSpecificVisitor)) {
					// [ 'Kind' => [ 'enter' => function ... , 'leave' => function ... ] ]
					return $kindSpecificVisitor;
				}
			}
		} else {
			$enterLeave = $isLeaving ? 'leave' : 'enter';
			if (array_key_exists($enterLeave, $visitor)) {
				$specificVisitor = $visitor[$enterLeave];
				if (is_callable($specificVisitor)) {
					// [ 'enter' => function ... , 'leave' => function ... ]
					return $specificVisitor;
				}

				if (is_array($specificVisitor) && array_key_exists($kind, $specificVisitor) && is_callable($specificVisitor[$kind])) {
					// [ 'enter' => [ 'Kind' => function ... ], 'leave' => [ 'Kind' => function ... ] ]
					return $specificVisitor[$kind];
				}
			}
		}

		return null;
	}

	<<__Memoize>>
	private static function getReflectionClass(string $kind, bool $typed) : ReflectionClass {
		if ($typed) {
			return new ReflectionClass("GraphQL\\Language\\AST\\" . $kind);
		}
		return new ReflectionClass("GraphQL\\Language\\AST\\Untyped\\" . $kind);
	}
}
