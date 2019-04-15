<?php
namespace Lyenrowe\BillParser;

use Lyenrowe\BillParser\FileParser\Alipay;
use Lyenrowe\BillParser\FileParser\Unionpay;
use Lyenrowe\BillParser\FileParser\UnionpayTxt;
use Lyenrowe\BillParser\FileParser\Wechat;
use Lyenrowe\BillParser\FileParser\WechatApp;
use Lyenrowe\BillParser\FileParser\WechatAppSettlement;
use Lyenrowe\BillParser\FileParser\WechatWap;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;

class FileParserFactory
{
    /**
     * @param $fullPath
     * @return Alipay|Unionpay|WechatApp|WechatAppSettlement|WechatWap
     * @throws Exception
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Reader\Exception\ReaderNotOpenedException
     */
    public static function create($fullPath)
    {
        if ('.txt' == substr($fullPath, -4)) { //银联接口下载的账单和后台导出不一样
            return new UnionpayTxt($fullPath);
        }
        $data = [];
        //$fullPath = base_path($path);
        $content = file_get_contents($fullPath);
        $fileEncoding = mb_detect_encoding($content, array('UTF-8','GB18030','GBK','LATIN1','BIG5')); //字符集次序很重要
        $fileType = strtolower(substr($fullPath, strrpos($fullPath, '.')+1));
        //$fileType = $fileType == 'xls' ? Type::XLSX : $fileType;
        if (!in_array($fileType, [Type::CSV, Type::XLSX])) {
            throw new Exception('文件格式只支持csv和xlsx');
        }

        $reader = ReaderFactory::create($fileType);
        $reader->setShouldFormatDates(true);
        if ($fileType == Type::CSV) {
            $reader->setEncoding($fileEncoding);
        }
        $reader->open($fullPath);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $data[] = $row;
            }
            //break;
        }
        $reader->close();
        if(!$data) {
            throw new Exception('未从excel解析到数据');
        }

        return self::getTypeByContent($data);
    }

    private static function getTypeByContent($content)
    {
        $firstRow = $content[0];
        if ('#支付宝业务明细查询' == $firstRow[0]) {
            return new Alipay($content);
        } elseif('商户代码' == $firstRow[0] && '银联系统日期' == $firstRow[5]) {
            return new Unionpay($content);
        } else {
            /*if ('#起始日期：' == mb_substr($firstRow[0], 0, 6)) { //应该多找一些微信字样以免误判
                return new Wechat($content);
            }*/
            if ('微信支付交易（交易+退款）对账文件' == $firstRow[0]) {
                return new WechatApp($content);
            } elseif('微信支付结算对账文件' == trim($firstRow[0])) {
                return new WechatAppSettlement($content);
            } elseif ('微信订单号' == $firstRow[5]) {
                return new WechatWap($content);
            }
            throw new Exception('不能从文件内容解析出支付方式,请上传约定标准格式的文件');
        }
    }
}