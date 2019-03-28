<?php
namespace Lyenrowe\BillParser\FileParser;

use Lyenrowe\BillParser\Exception;
use Lyenrowe\BillParser\FileParseAbstract;

class WechatWap extends FileParseAbstract
{
    use Traits\Stripe;

    const CHANNEL_NAME = 'wechat';

    protected function parse($collection)
    {
        $head = $collection[0];
        $this->checkHead($head);

        foreach ($collection as $row) {
            $row = $this->stripSpecialChar($row);
            if (!isset($row[6]) || !isset($row[12])) {
                continue;
            }
            $orderNum = $row[6];
            $amount = $row[12];
            if (empty($orderNum) || !is_numeric($amount)) {
                ++$this->notValidRowNum;
                continue;
            }

            $dealTime = $row[0];
            $tradeType = $this->tradeType($row[9]);
            $outTradeNo = $row[5];
            $originOrderNum = '';
            if (self::TRADE_TYPE_REFUND == $tradeType) {
                $originOrderNum = $orderNum;
                $orderNum = $row[15];
                //$outTradeNo = $row[14];
                $amount = $row[16];
            }
            $symbol = $this->getSymbol($tradeType);

            $this->rows[] = [
                'order_num' => $orderNum,
                'origin_order_num' => $originOrderNum,
                'out_trade_no' => $outTradeNo, //原订单号
                'trade_type' => $tradeType,
                'product_name' => $row[20],
                'amount' => $symbol * abs($amount),
                'service_fee' => -$symbol * abs($row[22]), //-0.006 * $amount,
                'pay_channel' => self::CHANNEL_NAME,
                'deal_time' => $dealTime, //购和退单如果在同一天会只有一条记录！时间记录的是退单的时间
                'finish_time' => $dealTime,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * @param $head
     * @throws Exception
     *
     * 18.11微信调整了交易账单导出字段 变化如下：\t隔开各列
     * 新格式账单字段	原格式账单字段	更新说明
     * 明细行
     * 特约商户号	子商户号	仅更新表头
     * 应结订单金额	总金额	应结订单金额是订单金额减去免充值券金额、应结算给商户的部分
     * 如果用户支付时使用了免充值券，应结订单金额<订单金额
     * 代金券金额	企业红包金额	代金券金额包含了一笔订单中用户使用的所有代金券金额汇总，包括充值券和免充值券
     * 充值券退款金额	企业红包退款金额	一笔退款中充值券部分的退款金额
     * 订单金额	无	列尾新增字段，一笔订单的总金额
     * 申请退款金额	无	列尾新增字段，申请退款的金额
     * 费率备注	无	列尾新增字段，对费率的备注说明
     * 汇总行
     * 应结订单总金额	总交易额	账单中所有应结订单金额汇总
     * 退款总金额	总退款金额	退款金额汇总，申请退款总金额减去免充值券退款金额
     * 充值券退款总金额	总企业红包退款金额	账单中所有充值券退款金额汇总
     * 订单总金额	无	列尾新增字段，账单中所有订单金额汇总
     * 申请退款总金额	无	列尾新增字段，账单中申请退款金额汇总
     */
    private function checkHead($head)
    {
        // 18.11微信调整了交易账单导出字段，更新4列，新增3列。总金额 更新成 应结订单金额
        if ('微信订单号' != $head[5] || '商户订单号' != $head[6]
            || !in_array(trim($head[12]), ['总金额', '应结订单金额']) || '交易状态' != $head[9]) {
            throw new Exception('微信商户类报表文件格式不符。head:'.print_r($head,true));
        }
    }

    protected function tradeType($type)
    {
        switch ($type) {
            case 'SUCCESS':
                return self::TRADE_TYPE_BUY;
            case 'REFUND':
                return self::TRADE_TYPE_REFUND;
            default:
                throw new Exception('不被识别的交易类型');
        }
    }
}