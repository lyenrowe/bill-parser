# bill-parser
parse alipay, wechat, unionpay bill file

# usage
```
//$path bill file full path;
$parser = FileParserFactory::create($path);
$rows = $parser->getData();
// save to db or something
/*foreach (array_chunk($rows, 1000) as $piece) {
    $model->insertIgnore($piece);
}*/
```
