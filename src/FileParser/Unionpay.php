<?php
namespace Lyenrowe\BillParser\FileParser;

use Lyenrowe\BillParser\Exception;
use Lyenrowe\BillParser\FileParseAbstract;

class Unionpay extends FileParseAbstract
{
    const CHANNEL_NAME = 'unionpay';

    public function parse($collection)
    {
        $head = $collection[0];
        $this->checkHead($head);

        //$negativeOrders = [];
        foreach ($collection as $row) {
            if (!isset($row[7])) {
                continue;
            }
            $orderNum = trim($row[7]);
            $amount = str_replace(',', '', $row[8]);
            if (empty($orderNum) || !is_numeric($amount) || '交易成功' != $row[10]) {
                ++$this->notValidRowNum;
                continue;
            }
            $dealTime = date('Y-m-d H:i:s', strtotime($row[3]));
            $tradeType = $this->tradeType(trim($row[1]));
            $outTradeNo = trim($row[12]);
            if (self::TRADE_TYPE_REFUND == $tradeType) {
                $outTradeNo = trim($row[13]);
            }
            $this->rows[] = [
                'order_num' => $orderNum,
                'out_trade_no' => $outTradeNo, //原订单号
                'trade_type' => $tradeType,
                'product_name' => null,
                'amount' => $amount,
                'service_fee' => -0.01 * $amount,
                'pay_channel' => self::CHANNEL_NAME,
                'deal_time' => $dealTime,
                'finish_time' => $dealTime,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    private function checkHead($head)
    {
        if ('银联系统日期' != $head[5] || '订单号' != $head[7]
            || '交易金额（元）' != $head[8] || '交易状态' != $head[10]) {
            throw new Exception('银联报表文件格式不符。head:'.print_r($head,true));
        }
    }

    private function tradeType($type)
    {
        switch ($type) {
            case '消费':
                return self::TRADE_TYPE_BUY;
            case '消费撤销':
                return self::TRADE_TYPE_REFUND;
            default:
                throw new Exception('不被识别的交易类型');
        }
    }
}