<?php
namespace Lyenrowe\BillParser\FileParser;

use Lyenrowe\BillParser\Exception;
use Lyenrowe\BillParser\FileParseAbstract;

class UnionpayTxt extends FileParseAbstract
{
    const CHANNEL_NAME = 'unionpay';

    protected function parse($filePath)
    {
        $lengthArray = array(3, 11, 11, 6, 10, 19, 12, 4, 2, 21, 2, 32, 2, 6, 10, 13, 13, 4, 15, 2, 2,
            6, 2, 4, 32, 1, 21, 15, 1, 15, 32, 13, 13, 8, 32, 13, 13, 12, 2, 1, 32, 98 );
        $collection = $this->parse_file($filePath, $lengthArray);

        $this->checkFirstRow($collection[0]);
        //$negativeOrders = [];
        /**
         * 5 交易传输时间 txnTime n10 MMDDhhmmss
         * 7 交易金额 txnAmt n12 左补零，补齐 12 位，为商户上送的金额
         * 10 查询流水号 queryId an21 取值同交易接口说明
         * 12 商户订单号 orderId ans32 取值同交易接口说明
         * 15 原始交易日期时间 n10 见注 4
         * 16 商户手续费 X+n12 见注 5
         * 17 结算金额 X+n12 取值为“清算金额”与“商户手续费”的扎差
         * 18 支付方式 payType an4 取值同交易接口说明
         * 20 交易类型 txnType n2 取值同交易接口说明
         * 24 账单类型 billType AN2 取值同交易接口说明
         * 27 原交易查询流水号 origQryId an21 取值同交易接口说明
         * 28 商户代码 merId n15 取值同交易接口说明
         * 33 清算净额 X+n12 取值为“结算金额”与“二级商户分账入账净额”的扎差
         * 36 优惠金额 X+n12 U 点抵扣金额，消费类正向交易为贷记 C，退货类反向交易为借记 D 。
         * 41 原始交易订单号 ans32 默认为空，预授权完成、退货、消费撤销、预授权撤销、预授权完成撤销交易才填写。
         * 42 清算金额 X+n12 商户本金的清算金额 //?
         */
        foreach ($collection as $row) {
            $orderNum = trim($row[12]);
            $amount = round(intval($row[7])/100, 2);

            // 不在跨年的零界点跑
            $year = date('Y');
            if (substr($row[5], 0, 4) > date('md')) {
                --$year;
            }
            $dealTime = date('Y-m-d H:i:s', strtotime($year.trim($row[5])));
            $tradeType = $this->tradeType(trim($row[20]));
            $outTradeNo = trim($row[10]);
            $originOrderNum = '';
            if (self::TRADE_TYPE_REFUND == $tradeType) {
                $originOrderNum = trim($row[41]);
                $outTradeNo = trim($row[27]);
            }
            $symbol = $this->getSymbol($tradeType);
            // 退订单无商户原订单交易流水号

            $this->rows[] = [
                'order_num' => $orderNum,
                'origin_order_num' => $originOrderNum,
                'out_trade_no' => $outTradeNo, //原订单号
                'trade_type' => $tradeType,
                'product_name' => null,
                'amount' => $symbol * abs($amount),
                'service_fee' => -0.01 * $symbol * abs($amount),
                'pay_channel' => self::CHANNEL_NAME,
                'deal_time' => $dealTime,
                'finish_time' => $dealTime,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    private function parse_file($filePath, $lengthArray)
    {
        if (!file_exists($filePath))
            return false;

        // 解析的结果MAP，key为对账文件列序号，value为解析的值
        $dataList = array();
        $s = "";
        foreach (file($filePath) as $s) {
            $dataMap = array();
            $leftIndex = 0;
            $rightIndex = 0;
            for ($i = 0; $i < count($lengthArray); $i++) {
                $rightIndex = $leftIndex + $lengthArray [$i];
                $filed = substr($s, $leftIndex, $lengthArray [$i]);
                $filed = iconv("GBK", "UTF-8", $filed);
                $leftIndex = $rightIndex + 1;
                $dataMap [$i + 1] = $filed;
            }
            $dataList [] = $dataMap;
        }
        return $dataList;
    }

    private function checkFirstRow($row)
    {
        if (empty($row[7]) || empty($row[12])) {
            throw new Exception('银联报表文件格式不符。first row:'.print_r($row,true));
        }
    }

    protected function tradeType($type)
    {
        switch ($type) {
            case '01':
                return self::TRADE_TYPE_BUY;
            case '04':
                return self::TRADE_TYPE_REFUND;
            default:
                throw new Exception('不被识别的交易类型');
        }
    }
}