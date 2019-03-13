<?php
/**
 * Created by PhpStorm.
 * User: lyen
 * Date: 2019/3/13
 * Time: 11:11
 */

namespace Lyenrowe\BillParser\FileParser\Traits;


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