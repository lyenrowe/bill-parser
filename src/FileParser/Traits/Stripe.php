<?php
/**
 * Created by PhpStorm.
 * User: gs
 * Date: 2019/3/13
 * Time: 11:11
 */

namespace Lyenrowe\BillParser\Traits;


trait Stripe
{
    private function stripSpecialChar($row)
    {
        foreach ($row as $k => $item) {
            $row[$k] = trim($item, '`');
        }
        return $row;
    }
}