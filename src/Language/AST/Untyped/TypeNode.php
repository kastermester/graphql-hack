<?hh

namespace GraphQL\Language\AST\Untyped;

// This is called Type in graphql-js
// but I had some issues with the autoloader plugin
// not understanding the class definition (parse error?).
// Need to figure out if it is a bug in the hhvm-autoloader

abstract class TypeNode extends Node {
}
