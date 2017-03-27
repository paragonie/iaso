# Iaso

[![Build Status](https://travis-ci.org/paragonie/iaso.svg?branch=master)](https://travis-ci.org/paragonie/iaso)
[![Latest Stable Version](https://poser.pugx.org/paragonie/iaso/v/stable)](https://packagist.org/packages/paragonie/iaso)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/iaso/v/unstable)](https://packagist.org/packages/paragonie/iaso)
[![License](https://poser.pugx.org/paragonie/iaso/license)](https://packagist.org/packages/paragonie/iaso)

Iaso is a powerful JSON toolkit for PHP 7+, intended for any organization that
builds or consumes JSON-based APIs.
 
Iaso was developed by  [Paragon Initiative Enterprises](http://paragonie.com) to
allow projects to build APIs without being vulnerable to
[hash-collision denial of service attacks from PHP's JSON functions](http://lukasmartinelli.ch/web/2014/11/17/php-dos-attack-revisited.html).

## Features

* HDoS resistant data structure (`ResultSet`)
* Basic JSON parser (returns `ResultSet` objects)
  * `Assoc` is a JSON object
  * `Ordered` is a JSON array

### Roadmap

* Contract-enforced JSON parser
  * Allows strict types, data limits
  * Throws an exception if any violations are found

## Usage Examples

### Simple JSON Parsing

```php
use ParagonIE\Iaso\JSON;
use ParagonIE\Iaso\ResultSet;

$data = JSON::parse($string);
var_dump($data instanceof ResultSet); /* bool(true) */
```
