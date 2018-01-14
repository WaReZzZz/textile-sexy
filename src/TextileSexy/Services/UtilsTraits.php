<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace TextileSexy\Services;

/**
 * Description of UtilsTraits
 *
 * @author Yaniv-PC
 */
trait UtilsTraits
{
    private function countItemsFromBasket($app)
    {
        $count = 0;
        $basket = $app['session']->get('basket');
        if (!is_null($basket)) {
            foreach ($basket as $item) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }
}
