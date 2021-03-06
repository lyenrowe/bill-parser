<?php
namespace Lyenrowe\BillParser\FileParser;

use Lyenrowe\BillParser\Exception;
use Lyenrowe\BillParser\FileParseAbstract;

class WechatAppSettlement extends FileParseAbstract
{
    use Traits\Stripe;

    const CHANNEL_NAME = 'wechat';

    protected function parse($collection)
    {
        $head = $collection[6];
        $this->checkHead($head);

        foreach ($collection as $row) {
            $row = $this->stripSpecialChar($row);
            if (!isset($row[7]) || !isset($row[16])) {
                continue;
            }
            $orderNum = $row[7];
            $amount = $row[16];
            if (empty($orderNum) || !is_numeric($amount)) {
                ++$this->notValidRowNum;
                continue;
            }
            $dealTime = $row[3];
            $tradeType = $this->tradeType($row[13]);
            $outTradeNo = $row[6];
            $originOrderNum = '';
            if (self::TRADE_TYPE_REFUND == $tradeType) {
                $originOrderNum = $orderNum;
                $orderNum = $row[9];
                //$outTradeNo = $row[8];
            }
            $symbol = $this->getSymbol($tradeType);

            $this->rows[] = [
                'order_num' => $orderNum,
                'origin_order_num' => $originOrderNum,
                'out_trade_no' => $outTradeNo, //原订单号
                'trade_type' => $tradeType,
                'product_name' => '',
                'amount' => $symbol * abs($amount),
                'service_fee' => -$symbol * abs($row[18]),
                'pay_channel' => self::CHANNEL_NAME,
                'deal_time' => $dealTime,
                'finish_time' => $row[0],
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    private function checkHead($head)
    {
        if ('微信订单号' != $head[6] || '商户订单号' != $head[7]
            || '订单金额（元）' != trim($head[16]) || '交易类型' != $head[13]) {
            throw new Exception('微信报表文件格式不符。head:'.print_r($head,true));
        }
    }

    protected function tradeType($type)
    {
        switch ($type) {
            case '支付':
                return self::TRADE_TYPE_BUY;
            case '退款':
                return self::TRADE_TYPE_REFUND;
            default:
                throw new Exception('不被识别的交易类型');
        }
    }
}