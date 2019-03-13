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

    abstract public function parse($collection);
}