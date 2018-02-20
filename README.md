# README

PHP implementation of the Lox language described in [Crafting Interpreters](http://craftinginterpreters.com/a-tree-walk-interpreter.html).

### Installing and running

Install dependencies:

`$ composer install`

Run the tests:

`$ ./vendor/bin/phpunit -c ./`

Run the REPL:

`$ ./bin/lox`

Run a source file:

`$ ./bin/lox /path/to/some/file.lox`

###Additional features

Below are features added beyond what the book covers.

####break statement

```
for (var i = 0; i < 10; i = i + 1) {
     if (i == 5) {
         break;
     }
 }
```

####Array class

```
var a = Array();
a.push(2);
a.push(5);
print a.get(0); // prints '2'
print a.pop(); // prints '5'
print a.length(); // prints '1'

```

####Input from command line via the Input class:

```
var in = Input();
var name = in.string("What's your name?");
var age = in.number("What's your age, " + name + "?");
```