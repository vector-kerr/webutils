# Web Utilities

## Overview

The Web Utilities package is intended to provide some utilities to make common
web-related operations quick and easy to perform.

The package currently contains helpers for:
* XML generation and parsing
* HTTP request generation and response parsing

This package is set up for integration with Composer and Laravel, but you
can just as easily download and use the heavy-lifting classes independently.


## Installation

1. Execute `composer require vector88/webutils` to include webutils in your
composer project.


## Configuration

If you want to use this package with [Laravel](//laravel.com), you will need to
add some entries to your `config/app.php` file to make use of the providers
and facades:

```php
<?php

'providers' => [
    ...
    Vector88\WebUtils\Providers\WebUtilsServiceProvider::class,
    ...
],

'aliases' => [
    ...
    'XmlHelper'          => Vector88\WebUtils\Facades\XmlHelper::class,
	'HttpRequestHelper'  => Vector88\WebUtils\Facades\HttpRequestHelper::class,
    ...
],
```

If you're not using this with Laravel, all you need to do is include the PHP
files you'd like to use! you can do this using the autoload script, or through
direct inclusion.

```php
<?php

require __DIR__ . "/vendor/autoload.php";

use Vector88\WebUtils\XmlHelper;
use Vector88\WebUtils\HttpRequestHelper;
```

## Usage

### HTTP Request Helper

The HTTP Request Helper is designed to make HTTP requests trivial, for requests
that live somewhere in complexity between `file_get_contents` and `curl_setopt`,
which is where many APIs seem to sit.

Here's an example that showcases most of the features:

```php
<?php

// Build a request (using Laravel with IOC)
$request = HttpRequestHelper::uri( $uri )
  ->authUsername( 'user' )
  ->authPassword( 'password' )
  ->method( 'POST' )
  ->header( 'Content-type', 'text/plain' )
  ->header( 'Cache-control', 'no-cache' )
  ->body( "Hello World! Here's some data..." );

// Execute the request
$response = $request->execute();

// If the request was successful:
if( $response->success ) {

  // Display the response code
  echo "{$response->code}<br />";

  // Display the response headers
  foreach( $response->headers as $header ) {
    echo "$header<br />";
  }

  // Display the response body
  echo "<br />";
  echo $response->body;

// Otherwise, if the request failed then display the error message and code
} else {
  echo "Request failed: {$response->errstr} ({$response->errno}).";

}
```

If you're not using Laravel, building the request changes a tiny bit:
```php
<?php

// Build a request (not using Laravel)
$request = new HttpRequestHelper();
$request->uri( $uri )
  ...
```

## XML Helper

The XML Helper is designed to make easy work of XML objects. At the document
level, it's able to:
* Load existing `DOMDocument` instances
* Create new XML trees
* Parse XML strings
* Output XML strings

At the node level, there are a number of methods available to make reading
and writing nodes trivial. On the reading level, there are also methods to
work with collections of nodes.

Heads up: most stuff simply returns `NULL` if it can't get a value.


### Using the XML Helper

If you are using the XML Helper with Laravel, you can take advantage of the IOC mechanisms.

```php
<?php
$xml = XmlHelper::loadStream( $xmlString );
...
```

If you are using the XML Helper outside of Laravel, you can just create a new instance and work with the methods from there.

```php
<?php
$xml = new XmlHelper();
$xml->loadStream( $xmlString );
...
```

### Loading an XML String

```php
<?php

// Load an XML String (using Laravel with IOC)
$xml = XmlHelper::loadStream( $xmlString );
```

### Using an Existing DOMDocument
```php
<?php

// Load a DOMDocument
$xml = XmlHelper::setElement( $document );
```

If you have a `DOMNode` that is attached to a `DOMDocument`, you can also
do this to treat the `DOMNode` as the "root element" for many of the operations
that the `XmlHelper` can perform:

```php
<?php

// Load a DOMNode
$xml = XmlHelper::setElement( $node );
```

### Creating a new XML object
```php
<?php

// Create an empty XML object (with a document element)
$xml = XmlHelper::create();
```

### Finding Elements

Say we have the following XML loaded into an `XmlHelper` instance named `$xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<catalogue>
  <books>
    <book isbn="9780582186552">
      <title>Hobbit, The</title>
      <author>Tolkien, J. R. R.</author>
      <year>1937</year>
    </book>
    <book isbn="9780718179465">
      <title>Rachel Khoo's Kitchen Notebook</title>
      <author>Khoo, R.</author>
      <year>2015</year>
    </book>
  </books>
</catalogue>
```

#### Retrieving Individual Elements

The following code could be used to retrieve the `<catalogue>` node (as a
  `DOMNode` instance):

```php
<?php
$catalogue = $xml->findFirst( 'catalogue' );
```

As the `XmlHelper` mostly backs on to `DOMXPath`, it's entirely possible to
provide an XPath string. To retrieve the first book element...

```php
<?php
$book = $xml->findFirst( '//book' );
```

Because XPath is XPath is XPath, you can retrieve the same element a million
ways:

```php
<?php
$book = $xml->findFirst( '/catalogue/books/book' );
$book = $xml->findFirst( '//books/book[starts-with(@isbn, "97805")]' );
$book = $xml->findFirst( '/catalogue//book[contains(author, "kien")]' );
...
```

#### Retrieving Collections of Elements

I'm sure you knew that when you saw `findFirst` there might also be a
`findAll` method.

```php
<?php
$books = $xml->findAll( '//books' );
```

Again, this method retrieves the `DOMNode` entries corresponding to the
elements that are retrieved.


### Retrieving an `XmlHelper` for Elements

If you'd rather retrieve an `XmlHelper` instance for the element you're
searching for, you can get one directly by using the `helper()` method:

```php
<?php
$catalogueHelper = $xml->helper( 'catalogue' );
```

Note that the `helper()` method doesn't quite operate the same as the `findX()`
methods: it actually prepends `./` to the XPath before it's processed. This
is handy because everything is relative to the current element, but if you're
trying to do other stuff, it's bad, because everything is relative to the
current element.

The reason that `./` is prepended is that it allows the helper to be re-used at
a sub-node level - that is to say, all element queries will always be relative
  to the current `DOMNode`, rather than the root of the `DOMDocument`.

For example:

```php
<?php
// GOOD. Do this.
$bookHelper = $xml->helper( 'catalogue/books/book' );

// OK. Because './' gets prepended, this is actually acceptable.
$bookHelper = $xml->helper( '/book' );

// BAD. Don't do this.
$bookHelper = $xml->helper( ''//book' );

```

### Retrieving multiple `XmlHelper` Instances for Elements

It is also possible to retrieve an array of `XmlHelper` instances for
a corresponding collection of `DOMNode`...

```php
<?php
$bookHelpers = $xml->helpers( 'catalogue/books/book' );
```

### Retrieving Element Values

This is where the useful stuff is in this library. If you have an `XmlHelper`
instance wrapped around one of the `<book />` elements listed above, you can
use some helper methods to retrieve the node values.

```php
<?php
// Retrieve a helper for the first book element that can be found
$book = $xml->helper( '/book' );

// Retrieve and display the book title and year
echo "Book Title: " . $book->string( "title" ) . "<br />";
echo "Year: " . $book->integer( "year" ) . "<br />";
```

If you want to do things in one fell swoop, you can make use of the wonders
of XPath and target the element indirectly.

```php
<?php
$title = $xml->string( '/book/title' );
$year = $xml->integer( '/book/year' );
```

In addition to `string()` and `integer()`, there is also `float()` which will
retrieve a floating point value, and `dateTime()` which will retrieve (you
guessed it) a DateTime element. the call to `dateTime()` requires a second
argument, which is the date format string. This format string is the same as
what would be used in `DateTime::createFromFormat()`, so
[look it up](http://php.net/manual/en/datetime.createfromformat.php) if you're
not sure what to put in there.


### Retrieving Multiple Element Values

There are also array versions of the above functions which allows you to get
an array of values of the given type from all nodes that match the given XPath.

```php
<?php
$allBookTitles = $xml->strings( '/book/title' );
$allBookYears = $xml->integers( '/book/year' );
```

Same goes for `floats()` and `dateTimes()`.


### Retrieving Attribute Values

It's probably what you'd expect:

```php
<?php
$book = $xml->helper( '/book' );
$isbn = $book->stringAttribute( 'isbn' );
```

Also available: `integerAttribute()` and `floatAttribute()`.


### DOM Manipulation

There are only a few simple methods here as it's not really part of the
original purpose of this package (but you're welcome to expand upon it!)

```php
<?php
$booksHelper = $xml->helper( '/catalogue/books' );

// Add (and get) a new book element to the books element
$newBookHelper = $booksHelper->add( 'book' );

// Use the XML helper to set some attributes and add additional children
$newBookHelper->setAttribute( 'isbn', '9780764555893' );
$newBookHelper->add( 'title', 'PHP and MySQL for Dummies' );
$newBookHelper->add( 'author', 'Valade, J' );
$newBookHelper->add( 'year', '2002' );
```

### XML String Generation

Use the `toString()` method to generate an XML string from the underlying
document. Note that the XML will be generated from the "root node" of the
helper that you're using.

```php
<?php
$xmlString = $xml->toString();
```

TODO: Add a way for you to retrieve the underlying element, the root element,
or a helper for one of those things, so you can get back to the root element
somehow.


### Namespace Integration

The XML Helper is able to work with elements in namespaces. Simply declare a
namespace, and then use your namespace prefix to work with the elements in
that namespace.

```php
<?php
$xml = XmlHelper::loadStream( $xmlString );
$xml->withNamespace( "http://example.com/animals", "ns" );
$animals = $xml->findAll( "ns:animals/ns:animal" );
```

When using an XML Helper to generate an XML Helper for a child or children,
namespaces are cloned and passed on at the time of generation, so you don't
need to redeclare namespaces for subsequent calls.

```php
<?php
$animalHelpers = $xml->helpers( "ns:animals/ns:animal" );
foreach( $animalHelpers as $animalHelper ) {
  echo $animalHelper->string( "ns:species" ) . "<br />";
  echo $animalHelper->string( "ns:subspecies" ) . "<br />";
}
```

### Great, but I can do all of this stuff using DOMDocument and DOMXPath...

To be fair, this helper is just a front for `DOMDocument` and `DOMXPath`, but
I made it because I'm lazy (ironic, I know).
