<?php
// php parseTest.php > test.log

require_once '../vendor/autoload.php';

use Lyenrowe\BillParser\FileParserFactory;

$excelDir = 'C:/Users/gs/Desktop/1-26';
$files = getExcelFile($excelDir);

foreach ($files as $path) {
    $parser = FileParserFactory::create($path);
    $rows = $parser->getData();
    //@todo 以csv格式写入解析的数据文本，在原excel中保留和设置相同列作为比较源，使用diff命令看差异
    echo $path,'\n','--------------------------------------------';
    print_r($rows);
    // save to db
    /*foreach (array_chunk($rows, 1000) as $piece) {
        $model->insertIgnore($piece);
    }*/
}

function getExcelFile($excelDir)
{
    $excelDir = trim($excelDir, '/');
    $files = [];
    //foreach (glob("$excelDir/*.(csv|xls|xlsx)") as $filename) { //是不支持复制正则，还是正则有问题！
    foreach (glob("$excelDir/*.csv") as $filename) {
        $files[] = $filename;
    }
    return $files;
}