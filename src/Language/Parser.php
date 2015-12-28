<?hh // strict

namespace GraphQL\Language;

use ConstVector;
use GraphQL\Assert;
use GraphQL\SyntaxException;
use HH\ImmVector;
use Shapes;

type ParseOptions = shape(
	'noLocation' => bool,
	'noSource' => bool,
);

class Parser {
	private Token $token;
	private Lexer $lexer;
	private Source $source;
	private ParseOptions $options;
	private int $prevEnd = 0;

	public function __construct(Source $source, bool $noLocation = false, bool $noSource = false){
		$this->source = $source;
		$lexer = new Lexer($source);
		$this->lexer = $lexer;
		$this->token = $lexer->nextToken();
		$this->options = shape(
			'noLocation' => $noLocation,
			'noSource' => $noSource
		);
	}

	private function getTokenDesc(Token $token) : string {
		$desc = $this->getTokenKindDesc($token['kind']);
		if (!is_null($token['value'])) {
			$value = $token['value'];
			return "$desc \"$value\"";
		}
		return $desc;
	}

	private function getTokenKindDesc(TokenKind $kind) : string {
		static $descs = [
			TokenKind::EOF => 'EOF',
			TokenKind::BANG => '!',
			TokenKind::DOLLAR => '$',
			TokenKind::PAREN_L => '(',
			TokenKind::PAREN_R => ')',
			TokenKind::SPREAD => '...',
			TokenKind::COLON => ':',
			TokenKind::EQUALS => '=',
			TokenKind::AT => '@',
			TokenKind::BRACKET_L => '[',
			TokenKind::BRACKET_R => ']',
			TokenKind::BRACE_L => '{',
			TokenKind::PIPE => '|',
			TokenKind::BRACE_R => '}',
			TokenKind::NAME => 'Name',
			TokenKind::VARIABLE => 'Variable',
			TokenKind::INT => 'Int',
			TokenKind::FLOAT => 'Float',
			TokenKind::STRING => 'String',
		];

		return $descs[$kind];
	}

	public function parse() : AST\Document {
		return $this->parseDocument();
	}

	public function parseValue() : AST\Value {
		return $this->parseValueLiteral(false);
	}

	public function parseName() : AST\Name {
		$token = $this->expect(TokenKind::NAME);
		$value = Assert::isNonNull($token['value']);
		return new AST\Name($this->loc($token['start']), $value);
	}

	/**
	 * Document : Definition+
	 */
	public function parseDocument() : AST\Document {
		$start = $this->token['start'];

		$definitions = [];

		do {
			$definitions[] = $this->parseDefinition();
		} while (!$this->skip(TokenKind::EOF));

		return new AST\Document($this->loc($start), new ImmVector($definitions));
	}

	/**
	 * OperationDefinition :
	 *   - SelectionSet
	 *   - OperationType Name? VariableDefinitions? Directives? SelectionSet
	 * OperationType: one of query mutation subscription
	 */
	public function parseOperationDefinition() : AST\OperationDefinition {
		$start = $this->token['start'];
		if ($this->peek(TokenKind::BRACE_L)) {
			$selectionSet = $this->parseSelectionSet();
			$loc = $this->loc($start);
			return new AST\OperationDefinition(
				$loc,
				AST\Operation::QUERY,
				$selectionSet,
				null, // name
				null, // variable defintiions
				ImmVector {}, // directives
			);
		}

		$operationToken = $this->expect(TokenKind::NAME);
		$operation = $operationToken['value'];

		$name = null;
		if ($this->peek(TokenKind::NAME)) {
			$name = $this->parseName();
		}

		$variableDefinitions = $this->parseVariableDefinitions();
		$directives = $this->parseDirectives();
		$selectionSet = $this->parseSelectionSet();
		$loc = $this->loc($start);
		return new AST\OperationDefinition(
			$loc,
			AST\Operation::assert($operation),
			$selectionSet,
			$name,
			$variableDefinitions,
			$directives,
		);
	}

	/**
	 * VariableDefinitions : ( VariableDefinition+ )
	 */
	public function parseVariableDefinitions() : ConstVector<AST\VariableDefinition> {
		return $this->peek(TokenKind::PAREN_L) ?
			$this->many(
				TokenKind::PAREN_L,
				inst_meth($this, 'parseVariableDefinition'),
				TokenKind::PAREN_R
			) :
			ImmVector {};
	}

	/**
	 * VariableDefinition : Variable : Type DefaultValue?
	 */
	public function parseVariableDefinition() : AST\VariableDefinition {
		$start = $this->token['start'];
		$variable = $this->parseVariable();
		$this->expect(TokenKind::COLON);
		$type = $this->parseType();
		$defaultValue = $this->skip(TokenKind::EQUALS) ? $this->parseValueLiteral(true) : null;
		$loc = $this->loc($start);

		return new AST\VariableDefinition(
			$loc,
			$variable,
			$type,
			$defaultValue,
		);
	}

	/**
	 * Variable : $ Name
	 */
	public function parseVariable() : AST\Variable {
		$start = $this->token['start'];
		$this->expect(TokenKind::DOLLAR);
		$name = $this->parseName();
		$loc = $this->loc($start);

		return new AST\Variable($loc, $name);
	}

	/**
	 * SelectionSet : { Selection+ }
	 */
	public function parseSelectionSet() : AST\SelectionSet {
		$start = $this->token['start'];

		$selections = $this->many(
			TokenKind::BRACE_L,
			inst_meth($this, 'parseSelection'),
			TokenKind::BRACE_R,
		);
		$loc = $this->loc($start);

		return new AST\SelectionSet(
			$loc,
			$selections,
		);
	}

	/**
	 * Selection :
	 *   - Field
	 *   - FragmentSpread
	 *   - InlineFragment
	 */
	public function parseSelection() : AST\Selection {
		return $this->peek(TokenKind::SPREAD) ?
			$this->parseFragment() :
			$this->parseField();
	}

	/**
	 * Field: Alias? Name Arguments? Directives? SelectionSet?
	 * Alias : Name :
	 */
	public function parseField() : AST\Field {
		$start = $this->token['start'];

		$nameOrAlias = $this->parseName();
		$alias = null;
		$name = $nameOrAlias;

		if ($this->skip(TokenKind::COLON)) {
			$alias = $nameOrAlias;
			$name = $this->parseName();
		}

		$arguments = $this->parseArguments();
		$directives = $this->parseDirectives();
		$selectionSet = $this->peek(TokenKind::BRACE_L) ? $this->parseSelectionSet() : null;
		$loc = $this->loc($start);

		return new AST\Field(
			$loc,
			$name,
			$alias,
			$arguments,
			$directives,
			$selectionSet,
		);
	}

	/**
	 * Arguments : ( Arguments+ )
	 */
	public function parseArguments() : ConstVector<AST\Argument> {
		return $this->peek(TokenKind::PAREN_L) ?
			$this->many(
				TokenKind::PAREN_L,
				inst_meth($this, 'parseArgument'),
				TokenKind::PAREN_R,
			) :
			ImmVector {};
	}

	/**
	 * Argument : Name : Value
	 */
	public function parseArgument() : AST\Argument {
		$start = $this->token['start'];
		$name = $this->parseName();
		$this->expect(TokenKind::COLON);
		$value = $this->parseValueLiteral(false);
		$loc = $this->loc($start);

		return new AST\Argument($loc, $name, $value);
	}

	/**
	 * Is both FragmentSpread and InlineFragment - would love to annotate
	 * this with the types
	 *
	 * FragmentSpread : ... FragmentName Directives?
	 * InlineFragment : ... TypeCondition? Directives? SelectionSet
	 */
	public function parseFragment(): AST\Selection {
		$start = $this->token['start'];
		$this->expect(TokenKind::SPREAD);

		if ($this->peek(TokenKind::NAME) && $this->token['value'] !== 'on') {
			$fragmentName = $this->parseFragmentName();
			$directives = $this->parseDirectives();
			$loc = $this->loc($start);
			return new AST\FragmentSpread($loc, $fragmentName, $directives);
		}

		$typeCondition = null;
		if ($this->peek(TokenKind::NAME) && $this->token['value'] === 'on') {
			$this->advance();
			$typeCondition = $this->parseNamedType();
		}
		$directives = $this->parseDirectives();
		$selectionSet = $this->parseSelectionSet();
		$loc = $this->loc($start);

		return new AST\InlineFragment(
			$loc,
			$selectionSet,
			$typeCondition,
			$directives
		);
	}

	/**
	 * FragmentDefinition :
	 *   - fragment FragmentName on TypeCondition Directives? SelectionSet
	 *
	 * TypeCondition : NamedType
	 */
	public function parseFragmentDefinition() : AST\FragmentDefinition {
		$start = $this->token['start'];
		$this->expectKeyword('fragment');

		$name = $this->parseFragmentName();
		$this->expectKeyword('on');
		$typeCondition = $this->parseNamedType();
		$directives = $this->parseDirectives();
		$selectionSet = $this->parseSelectionSet();
		$loc = $this->loc($start);

		return new AST\FragmentDefinition(
			$loc,
			$name,
			$selectionSet,
			$typeCondition,
			$directives,
		);
	}

	/**
	 * FragmentName : Name but not 'on'
	 */
	public function parseFragmentName() : AST\Name {
		if ($this->peek(TokenKind::NAME) && $this->token['value'] === 'on') {
			throw $this->unexpected();
		}

		return $this->parseName();
	}
	/**
	 * Value[Const] :
	 *   - [~Const] Variable
	 *   - IntValue
	 *   - FloatValue
	 *   - StringValue
	 *   - BooleanValue
	 *   - EnumValue
	 *   - ListValue[?Const]
	 *   - ObjectValue[?Const]
	 *
	 * BooleanValue : one of `true` `false`
	 *
	 * EnumValue : Name but not `true`, `false` or `null`
	 */
	public function parseValueLiteral(bool $isConst) : AST\Value {
		$token = $this->token;
		switch ($token['kind']) {
			case TokenKind::BRACKET_L:
				return $this->parseList($isConst);
			case TokenKind::BRACE_L:
				return $this->parseObject($isConst);
			case TokenKind::INT:
				$this->advance();
				$value = Assert::isNonNull($token['value']);
				return new AST\IntValue(
					$this->loc($token['start']),
					$value,
				);
			case TokenKind::STRING:
				$this->advance();
				$value = Assert::isNonNull($token['value']);
				return new AST\StringValue(
					$this->loc($token['start']),
					$value,
				);
			case TokenKind::NAME:
				if ($token['value'] === 'true' || $token['value'] === 'false') {
					$this->advance();
					return new AST\BooleanValue(
						$this->loc($token['start']),
						$token['value'] === 'true',
					);
				} else if ($token['value'] !== 'null') {
					$this->advance();
					$value = Assert::isNonNull($token['value']);
					return new AST\EnumValue(
						$this->loc($token['start']),
						$value,
					);
				}
				break;
			case TokenKind::DOLLAR:
				if (!$isConst) {
					return $this->parseVariable();
				}
				break;
			default:
				// Fall through to throwing syntax exception
		}
		throw $this->unexpected();
	}

	public function parseConstValue() : AST\Value {
		return $this->parseValueLiteral(true);
	}

	public function parseValueValue() : AST\Value {
		return $this->parseValueLiteral(false);
	}

	/**
	 * ListValue[Const] :
	 *   - [ Value[?Const]* ]
	 */
	public function parseList(bool $isConst) : AST\ListValue {
		$start = $this->token['start'];
		$item = $isConst ? inst_meth($this, 'parseConstValue') : inst_meth($this, 'parseValueValue');
		$values = $this->any(TokenKind::BRACKET_L, $item, TokenKind::BRACKET_R);
		$loc = $this->loc($start);

		return new AST\ListValue($loc, $values);
	}

	/**
	 * ObjectValue[Const] :
	 *   - { ObjectField[?Const]* }
	 */
	public function parseObject(bool $isConst) : AST\ObjectValue {
		$start = $this->token['start'];
		$item = $isConst ? inst_meth($this, 'parseConstObjectField') : inst_meth($this, 'parseValueObjectField');
		$values = $this->any(TokenKind::BRACE_L, $item, TokenKind::BRACE_R);
		$loc = $this->loc($start);

		return new AST\ObjectValue($loc, $values);
	}

	public function parseConstObjectField() : AST\ObjectField {
		return $this->parseObjectField(true);
	}

	public function parseValueObjectField() : AST\ObjectField {
		return $this->parseObjectField(false);
	}

	/**
	 * ObjectField[Const] : Name : Value[?Const]
	 */
	public function parseObjectField(bool $isConst) : AST\ObjectField {
		$start = $this->token['start'];
		$name = $this->parseName();
		$this->expect(TokenKind::COLON);
		$value = $this->parseValueLiteral($isConst);
		$loc = $this->loc($start);

		return new AST\ObjectField($loc, $name, $value);
	}

	public function parseDirectives() : ConstVector<AST\Directive> {
		$directives = Vector { };
		while ($this->peek(TokenKind::AT)) {
			$directives->add($this->parseDirective());
		}

		return new ImmVector($directives);
	}

	/**
	 * Directive: @ Name Arguments?
	 */
	public function parseDirective() : AST\Directive {
		$start = $this->token['start'];
		$this->expect(TokenKind::AT);
		$name = $this->parseName();
		$arguments = $this->parseArguments();
		$loc = $this->loc($start);

		return new AST\Directive($loc, $name, $arguments);
	}

	/**
	 * Type :
	 *   - NamedType
	 *   - ListType
	 *   - NonNullType
	 */
	public function parseType() : AST\TypeNode {
		$start = $this->token['start'];
		$type = null;
		if ($this->skip(TokenKind::BRACKET_L)) {
			$type = $this->parseType();
			$this->expect(TokenKind::BRACKET_R);
			$type = new AST\ListType($this->loc($start), $type);
		} else {
			$type = $this->parseNamedType();
		}

		if ($this->skip(TokenKind::BANG)) {
			return new AST\NonNullType(
				$this->loc($start),
				$type,
			);
		}

		return $type;
	}

	/**
	 * TypeDefinition :
	 *   - ObjectTypeDefinition
	 *   - InterfaceTypeDefinition
	 *   - UnionTypeDefinition
	 *   - ScalarTypeDefinition
	 *   - EnumTypeDefinition
	 *   - InputObjectTypeDefinition
	 *   - TypeExtensionDefinition
	 */
	public function parseTypeDefinition() : AST\TypeDefinition {
		if (!$this->peek(TokenKind::NAME)) {
			throw $this->unexpected();
		}

		$value = Assert::isNonNull($this->token['value']);

		switch ($value) {
			case 'type':
				return $this->parseObjectTypeDefinition();
			case 'interface':
				return $this->parseInterfaceTypeDefinition();
			case 'union':
				return $this->parseUnionTypeDefinition();
			case 'scalar':
				return $this->parseScalarTypeDefinition();
			case 'input':
				return $this->parseInputObjectTypeDefinition();
			case 'extend':
				return $this->parseTypeExtensionDefinition();
			default:
				throw $this->unexpected();
		}
	}

	/**
	 * NamedType : Name
	 */
	public function parseNamedType() : AST\NamedType {
		$start = $this->token['start'];
		$name = $this->parseName();
		$loc = $this->loc($start);

		return new AST\NamedType($loc, $name);
	}

	/**
	 * Definition :
	 *   - OperationDefinition
	 *   - FragmentDefinition
	 *   - TypeDefinition
	 */
	public function parseDefinition() : AST\Definition {
		if ($this->peek(TokenKind::BRACE_L)) {
			return $this->parseOperationDefinition();
		}

		if ($this->peek(TokenKind::NAME)) {
			$value = Assert::isNonNull($this->token['value']);
			switch ($value) {
				case 'query':
				// FALLTHROUGH
				case 'mutation':
				// Note: subscriptions are not in the spec yet
				case 'subscription':
					return $this->parseOperationDefinition();
				case 'fragment':
					return $this->parseFragmentDefinition();

				// FALLTHROUGH
				case 'type':
				case 'interface':
				case 'union':
				case 'scalar':
				case 'enum':
				case 'input':
				case 'extend':
					return $this->parseTypeDefinition();
			}
		}

		throw $this->unexpected();
	}

	/**
	 * ObjectTypeDefinition : type Name ImplementsInterfaces? { FieldDefinition+ }
	 */
	public function parseObjectTypeDefinition() : AST\ObjectTypeDefinition {
		$start = $this->token['start'];
		$this->expectKeyword('type');
		$name = $this->parseName();
		$interfaces = $this->parseImplementsInterfaces();
		$fields = $this->any(
			TokenKind::BRACE_L,
			inst_meth($this, 'parseFieldDefinition'),
			TokenKind::BRACE_R,
		);
		$loc = $this->loc($start);

		return new AST\ObjectTypeDefinition(
			$loc,
			$name,
			$fields,
			$interfaces
		);
	}

	/**
	 * ImplementsInterfaces : (implements NamedType+)?
	 */
	public function parseImplementsInterfaces() : ConstVector<AST\NamedType> {
		$types = Vector {};
		if ($this->peek(TokenKind::NAME) && $this->token['value'] === 'implements') {
			$this->advance();
			do {
				$types->add($this->parseNamedType());
			} while (!$this->peek(TokenKind::BRACE_L));
		}

		return new ImmVector($types);
	}

	/**
	 * FieldDefinition : Name ArgumentsDefinition? : Type
	 */
	public function parseFieldDefinition() : AST\FieldDefinition {
		$start = $this->token['start'];
		$name = $this->parseName();
		$args = $this->parseArgumentDefs();
		$this->expect(TokenKind::COLON);
		$type = $this->parseType();
		$loc = $this->loc($start);

		return new AST\FieldDefinition(
			$loc,
			$name,
			$args,
			$type,
		);
	}

	public function parseArgumentDefs() : ConstVector<AST\InputValueDefinition> {
		if (!$this->peek(TokenKind::PAREN_L)) {
			return ImmVector {};
		}

		return $this->many(
			TokenKind::PAREN_L,
			inst_meth($this, 'parseInputValueDef'),
			TokenKind::PAREN_R
		);
	}

	public function parseInputValueDef() : AST\InputValueDefinition {
		$start = $this->token['start'];
		$name = $this->parseName();
		$this->expect(TokenKind::COLON);
		$type = $this->parseType();
		$defaultValue = null;
		if ($this->skip(TokenKind::EQUALS)) {
			$defaultValue = $this->parseConstValue();
		}

		$loc = $this->loc($start);

		return new AST\InputValueDefinition(
			$loc,
			$name,
			$type,
			$defaultValue,
		);
	}

	/**
	 * InterfaceTypeDefinition : interface Name { FieldDefinition+ }
	 */
	public function parseInterfaceTypeDefinition() : AST\InterfaceTypeDefinition {
		$start = $this->token['start'];
		$this->expectKeyword('interface');
		$name = $this->parseName();
		$fields = $this->any(
			TokenKind::BRACE_L,
			inst_meth($this, 'parseFieldDefinition'),
			TokenKind::BRACE_R,
		);
		$loc = $this->loc($start);

		return new AST\InterfaceTypeDefinition(
			$loc,
			$name,
			$fields,
		);
	}

	/**
	 * UnionTypeDefinition : union Name = UnionMembers
	 */
	public function parseUnionTypeDefinition() : AST\UnionTypeDefinition {
		$start = $this->token['start'];
		$this->expectKeyword('union');
		$name = $this->parseName();
		$this->expect(TokenKind::EQUALS);
		$types = $this->parseUnionMembers();
		$loc = $this->loc($start);

		return new AST\UnionTypeDefinition(
			$loc,
			$name,
			$types,
		);
	}

	/**
	 * UnionMembers :
	 *   - NamedType
	 *   - UnionMembers | NamedType
	 */
	public function parseUnionMembers() : ConstVector<AST\NamedType> {
		$members = Vector { };
		do {
			$members->add($this->parseNamedType());
		} while ($this->skip(TokenKind::PIPE));

		return new ImmVector($members);
	}

	/**
	 * ScalarTypeDefinition : scalar Name
	 */
	public function parseScalarTypeDefinition() : AST\ScalarTypeDefinition {
		$start = $this->token['start'];
		$this->expectKeyword('scalar');
		$name = $this->parseName();
		$loc = $this->loc($start);
		return new AST\ScalarTypeDefinition(
			$loc,
			$name
		);
	}

	/**
	 * EnumTypeDefinition : enum Name { EnumValueDefinition+ }
	 */
	public function parseEnumTypeDefinition() : AST\EnumTypeDefinition {
		$start = $this->token['start'];
		$this->expectKeyword('enum');
		$name = $this->parseName();
		$values = $this->many(
			TokenKind::BRACE_L,
			inst_meth($this, 'parseEnumValueDefinition'),
			TokenKind::BRACE_R,
		);
		$loc = $this->loc($start);

		return new AST\EnumTypeDefinition(
			$loc,
			$name,
			$values,
		);
	}

	/**
	 * EnumValueDefinition : EnumValue
	 *
	 * EnumValue: Name
	 */
	public function parseEnumValueDefinition() : AST\EnumValueDefinition {
		$start = $this->token['start'];
		$name = $this->parseName();
		$loc = $this->loc($start);

		return new AST\EnumValueDefinition(
			$loc,
			$name
		);
	}

	/**
	 * InputObjectTypeDefinition : input Name { InputValueDefinition* }
	 */
	public function parseInputObjectTypeDefinition() : AST\InputObjectTypeDefinition {
		$start = $this->token['start'];
		$this->expectKeyword('input');
		$name = $this->parseName();
		$fields = $this->any(
			TokenKind::BRACE_L,
			inst_meth($this, 'parseInputValueDef'),
			TokenKind::BRACE_R
		);
		$loc = $this->loc($start);

		return new AST\InputObjectTypeDefinition(
			$loc,
			$name,
			$fields,
		);
	}

	/**
	 * TypeExtensionDefinition : extend ObjectTypeDefinition
	 */
	public function parseTypeExtensionDefinition() : AST\TypeExtensionDefinition {
		$start = $this->token['start'];
		$this->expectKeyword('extend');
		$definition = $this->parseObjectTypeDefinition();
		$loc = $this->loc($start);
		return new AST\TypeExtensionDefinition(
			$loc,
			$definition,
		);
	}

	private function loc(int $start) : ?Location {
		if ($this->options['noLocation']) {
			return null;
		}

		if ($this->options['noSource']) {
			return new Location($start, $this->prevEnd);
		}

		return new Location($start, $this->prevEnd, $this->source);
	}

	private function advance() : void {
		$prevEnv = $this->token['end'];
		$this->prevEnd = $prevEnv;
		$this->token = $this->lexer->nextToken($prevEnv);
	}

	private function peek(TokenKind $kind) : bool {
		return $this->token['kind'] === $kind;
	}

	private function skip(TokenKind $kind) : bool {
		$match = $this->token['kind'] === $kind;
		if ($match) {
			$this->advance();
		}

		return $match;
	}

	private function expect(TokenKind $kind) : Token {
		$token = $this->token;
		if ($token['kind'] === $kind) {
			$this->advance();
			return $token;
		}

		throw new SyntaxException(
			$this->source,
			$token['start'],
			"Expected " . $this->getTokenKindDesc($kind) . ", found "
			. $this->getTokenDesc($token)
		);
	}

	private function expectKeyword(string $value) : Token {
		$token = $this->token;

		if ($token['kind'] === TokenKind::NAME && $token['value'] === $value) {
			$this->advance();
			return $token;
		}

		throw new SyntaxException(
			$this->source,
			$token['start'],
			'Expected "' . $value . '", found ' . $this->getTokenDesc($token)
		);
	}

	private function unexpected(?Token $atToken = null) : SyntaxException {
		$token = is_null($atToken) ? $this->token : $atToken;

		return new SyntaxException(
			$this->source,
			$token['start'],
			'Unexpected ' . $this->getTokenDesc($token)
		);
	}

	private function any<T>(TokenKind $openKind, (function() : T) $parseFn, TokenKind $closeKind) : ConstVector<T> {
		$this->expect($openKind);
		$nodes = Vector {};

		while (!$this->skip($closeKind)) {
			$nodes->add($parseFn());
		}

		return new ImmVector($nodes);
	}

	private function many<T>(TokenKind $openKind, (function() : T) $parseFn, TokenKind $closeKind) : ConstVector<T> {
		$this->expect($openKind);
		$nodes = Vector { $parseFn() };
		while (!$this->skip($closeKind)) {
			$nodes->add($parseFn());
		}

		return new ImmVector($nodes);
	}
}
