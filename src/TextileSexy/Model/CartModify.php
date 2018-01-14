<?php

namespace TextileSexy\Model;

use ApaiIO\Operations\CartCreate;

/**
 * A cart Modify operation
 */
class CartModify extends CartCreate
{
    private $itemCounter = 1;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'CartModify';
    }

    /**
     * Returns the cart id
     *
     * @return string
     */
    public function getCartId()
    {
        return $this->getSingleOperationParameter('CartId');
    }

    /**
     * Sets the cart id
     *
     * @param string $cartId
     */
    public function setCartId($cartId)
    {
        $this->parameters['CartId'] = $cartId;
    }

    /**
     * Returns the HMAC
     *
     * @return mixed
     */
    public function getHMAC()
    {
        return $this->getSingleOperationParameter('HMAC');
    }

    /**
     * Sets the HMAC
     *
     * @param string $HMAC
     */
    public function setHMAC($HMAC)
    {
        $this->parameters['HMAC'] = $HMAC;
    }

    /**
     * Adds an item to the Cart
     *
     * @param string  $cartItemId The ASIN or OfferListingId Number of the item
     * @param integer $quantity   How much you want to add
     */
    public function removeItem($cartItemId, $quantity)
    {
        $this->parameters['Item.' . $this->itemCounter . '.CartItemId'] = $cartItemId;
        $this->parameters['Item.' . $this->itemCounter . '.Quantity'] = $quantity;

        //$this->itemCounter = 0;
    }
}
