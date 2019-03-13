<?php
namespace Lyenrowe\BillParser\FileParser;

use Lyenrowe\BillParser\Exception;
use Lyenrowe\BillParser\FileParseAbstract;

class Wechat extends FileParseAbstract
{
    const CHANNEL_NAME = 'wechat';

    protected function parse($collection)
    {
        $head = $collection[3];
        $this->checkHead($head);

        foreach ($collection as $row) {
            if (!isset($row[2])) {
                continue;
            }
            $orderNum = trim($row[2]);
            if (empty($orderNum) || !is_numeric($row[11]) || !in_array($row[9], ['买家已支付', '全额退款完成', '部分退款完成'])) {
                ++$this->notValidRowNum;
                continue;
            }
            $dealTime = date('Y-m-d H:i:s', strtotime($row[0]));
            $tradeType = $this->tradeType(trim($row[9]));
            $symbol = $this->getSymbol($tradeType);

            $this->rows[] = [
                'order_num' => $orderNum,
                'out_trade_no' => trim($row[1]),
                'trade_type' => $tradeType,
                'product_name' => null,
                'amount' => $symbol * abs($row[11]),
                'service_fee' => -0.006 * $symbol * abs($row[11]),
                'pay_channel' => self::CHANNEL_NAME,
                'deal_time' => $dealTime,
                'finish_time' => date('Y-m-d H:i:s', strtotime($row[10])),
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    private function checkHead($head)
    {
        if ('微信支付单号' != $head[1] || '商户订单号' != $head[2]
            || '交易金额(元)' != trim($head[11]) || '交易状态' != $head[9]) {
            throw new Exception('微信报表文件格式不符。head:'.print_r($head,true));
        }
    }

    protected function tradeType($type)
    {
        switch ($type) {
            case '买家已支付':
                return self::TRADE_TYPE_BUY;
            case '全额退款完成':
            case '部分退款完成':
                return self::TRADE_TYPE_REFUND;
            default:
                throw new Exception('不被识别的交易类型');
        }
    }
}