# bill-parser
parse alipay, wechat, unionpay bill file.

解析支付平台账单文件，包括：支付宝，微信，银联等支付的账单文件。
账单文件可通过接口下载，也可财务下载。
使用非常简单，传递要解析的文件路径，
程序根据文件头部内容分析文件类型，然后使用相应解析类解析账单文件

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

