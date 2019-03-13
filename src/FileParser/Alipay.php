<?php
namespace Lyenrowe\BillParser\FileParser;

use Lyenrowe\BillParser\Exception;
use Lyenrowe\BillParser\FileParseAbstract;

class Alipay extends FileParseAbstract
{
    const CHANNEL_NAME = 'alipay';

    public function parse($collection)
    {
        $head = $collection[4];
        $this->checkHead($head);

        foreach ($collection as $row) {
            if (!isset($row[1])) {
                continue;
            }
            if (empty($row[1]) || !is_numeric($row[11])) {
                ++$this->notValidRowNum;
                continue;
            }
            //$originOrderNum = trim($row[1]);
            $tradeType = $this->tradeType(trim($row[2]));
            $orderNum = $tradeType == 2 ? trim($row[21]) : trim($row[1]);
            $symbol = $this->getSymbol($tradeType);

            $this->rows[] = [
                'order_num' => $orderNum,
                'out_trade_no' => trim($row[0]), //原订单号
                'trade_type' => $tradeType,
                'product_name' => $row[3],
                'amount' => $symbol * abs($row[11]),
                'service_fee' => -$symbol * abs($row[22]),
                'pay_channel' => self::CHANNEL_NAME,
                'deal_time' => date('Y-m-d H:i:s', strtotime($row[4])),
                'finish_time' => date('Y-m-d H:i:s', strtotime($row[5])),
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    private function checkHead($head)
    {
        if ('支付宝交易号' != $head[0] || '商户订单号' != $head[1]
            || '订单金额（元）' != $head[11] || '服务费（元）' != $head[22]) {
            throw new Exception('支付宝报表文件格式不符。head:'.print_r($head,true));
        }
    }

    protected function tradeType($type)
    {
        switch ($type) {
            case '交易':
                return self::TRADE_TYPE_BUY;
            case '退款':
                return self::TRADE_TYPE_REFUND;
            default:
                throw new Exception('不被识别的交易类型');
        }
    }
}