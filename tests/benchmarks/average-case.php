<?php
use ParagonIE\Iaso\JSON;

require '../../vendor/autoload.php';

JSON::parse('[]');

$string = '{
  "data": [{
    "type": "articles",
    "id": "1",
    "attributes": {
      "title": "JSON API paints my bikeshed!",
      "body": "The shortest article. Ever.",
      "created": "2015-05-22T14:56:29.000Z",
      "updated": "2015-05-22T14:56:28.000Z"
    },
    "relationships": {
      "author": {
        "data": {"id": "41", "type": "people"},
        "data": {"id": "42", "type": "people"}
      }
    }
  }],
  "included": [
    {
      "type": "people",
      "id": "41",
      "attributes": {
        "name": "Jane",
        "age": 30,
        "gender": "genderfluid"
      }
    },
    {
      "type": "people",
      "id": "42",
      "attributes": {
        "name": "John",
        "age": 80,
        "gender": "male"
      }
    }
  ]
}';

$i = 0;
$start = $end = 0.000;

$start = \microtime(true);
for ($i = 0; $i < 1000; ++$i) {
    $native = \json_decode($string);
}
$end = \microtime(true);

$diff = $end - $start;
echo number_format($diff, 3) . ' seconds (native)', PHP_EOL;

$start = \microtime(true);
for ($i = 0; $i < 1000; ++$i) {
    $native = JSON::parse($string);
}
$end = \microtime(true);

$diff = $end - $start;
echo number_format($diff, 3) . ' seconds (Iaso)', PHP_EOL;
