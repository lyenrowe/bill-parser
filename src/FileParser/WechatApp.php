<?php
namespace Lyenrowe\BillParser\FileParser;

use Lyenrowe\BillParser\Exception;
use Lyenrowe\BillParser\FileParseAbstract;

class WechatApp extends FileParseAbstract
{
    use Traits\Stripe;

    const CHANNEL_NAME = 'wechat';

    protected function parse($collection)
    {
        $head = $collection[6];
        $this->checkHead($head);

        foreach ($collection as $row) {
            $row = $this->stripSpecialChar($row);
            if (!isset($row[4]) || !isset($row[15])) {
                continue;
            }
            $orderNum = $row[4];
            $amount = $row[15];
            if (empty($orderNum) || !is_numeric($amount)) {
                ++$this->notValidRowNum;
                continue;
            }
            $dealTime = $row[0]; //date('Y-m-d H:i:s', strtotime($row[0])); //购和退单独记录时间都是对的
            $tradeType = $this->tradeType($row[9]);
            $outTradeNo = $row[3];
            if (self::TRADE_TYPE_REFUND == $tradeType) {
                $orderNum = $row[11];
                //$outTradeNo = $row[10];
            }
            $symbol = $this->getSymbol($tradeType);

            $this->rows[] = [
                'order_num' => $orderNum,
                'out_trade_no' => $outTradeNo,
                'trade_type' => $tradeType,
                'product_name' => $row[13],
                'amount' => $symbol * abs($amount),
                'service_fee' => -0.006 * $symbol * abs($amount),
                'pay_channel' => self::CHANNEL_NAME,
                'deal_time' => $dealTime,
                'finish_time' => $dealTime,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    private function checkHead($head)
    {
        if ('微信订单号' != $head[3] || '商户订单号' != $head[4]
            || '订单金额/申请退款金额（元）' != trim($head[15]) || '交易类型' != $head[9]) {
            throw new Exception('微信报表文件格式不符。head:'.print_r($head,true));
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