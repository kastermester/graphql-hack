# GraphQL Hack
This project aims to be a port of [graphql/graphql-js](https://github.com/graphql/graphql-js) written in [Hack](http://hacklang.org/).

## Reasoning ##
There already exists a PHP port at [webonyx/graphql-php](https://github.com/webonyx/graphql-php), this port will be different in that it tries to be idiomatic as a Hack port. That means it will use Hack collections whenever possible, but more importantly, like the original JavaScript implementation, it can support asynchronous execution, which will allow one to use a Hack port (not sure if it exists, yet, but it should be easy to create) of (facebook/dataloader)[https://github.com/facebook/dataloader] to implement efficient fetching, caching of data.

I intend to create this port in such a way that it will be easy for me to continue keeping it up to date with the original JavaScript implementation. I will port all the original tests as well. The hope is that, once this port is finished and the JavaScript implementation keeps changing, I can watch the changes and apply them in this implementation as well.

## Status ##

- [x] graphql/language - Almost all of this module is ported. With exception of `Visitor::visitInParallel` and `Visitor::visitWithTypeInfo`. This means that it is possible to lex and parse the GraphQL language and it is possible to pretty print GraphQL back out again.
- [ ] graphql/type - This will be the next in line. This module the one responsible for defining a GraphQL schema.
- [ ] graphql/language : missing pieces - Implement the remaining visitor methods described above.
- [ ] graphql/validation - Implement the validation rules as per the GraphQL spec.
- [ ] graphql/execution - Implement the execution logic to actually execute a GraphQL query.

There might be pieces missing in the above mentioned plan, I will add them as I discover them. Like stated earlier, I intend this to be a complete port of `graphql-js`.
