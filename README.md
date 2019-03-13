# bill-parser
parse alipay, wechat, unionpay bill file

## usage
```
//$path bill file full path;
$parser = FileParserFactory::create($path);
$rows = $parser->getData();
// save to db or something
/*foreach (array_chunk($rows, 1000) as $piece) {
    $model->insertIgnore($piece);
}*/
```
## Installation
Via [Composer](http://getcomposer.org/). To install, simply add it
to your `composer.json` file:

    "lyenrowe/bill-parser": "^1.0",

Or run command:

    composer require lyenrowe/bill-parser:1.*

