<?php

namespace Lyenrowe\BillParser;

Abstract Class FileParseAbstract
{
    protected $rows = [];
    protected $notValidRowNum = 0;
    const TRADE_TYPE_BUY = 1;
    const TRADE_TYPE_REFUND = 2;

    public function __construct($collection)
    {
        $this->parse($collection);
    }

    abstract protected function parse($collection);

    /**
     * @param $type
     * @return int self::TRADE_TYPE_BUY|self::TRADE_TYPE_REFUND
     * @throws Exception
     */
    abstract protected function tradeType($type);

    public function getData()
    {
        return $this->rows;
    }

    protected function getSymbol($tradeType)
    {
        $arr = [self::TRADE_TYPE_BUY => 1, self::TRADE_TYPE_REFUND => -1];
        // $tradeType 传递进来是经过$this->tradeType()检查的，这里不检查是否超出边界
        return $arr[$tradeType];
    }
}