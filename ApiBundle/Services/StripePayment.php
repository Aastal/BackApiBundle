<?php

namespace Geoks\ApiBundle\Services;

use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Token;
use Stripe\Customer;
use Stripe\Error\Card;

class StripePayment
{
    /**
     * @var string
     */
    private $publicKey;

    public function __construct($privateKey, $publicKey)
    {
        Stripe::setApiKey($privateKey);
        $this->publicKey = $publicKey;
    }

    /**
     * @param string $email
     * @param array $card
     * @return null|string
     */
    public function saveCard($email, $card)
    {
        try {
            $token = Token::create(['card' => $card]);
            $user = Customer::create(array(
                "email" => $email,
                "source" => $token,
            ));

            $user = $user->id;
        } catch(Card $e) {
            $user = null;
        }

        return $user;
    }

    /**
     * @param array $card
     * @param integer $amount
     * @param string $currency
     * @return null|string
     */
    public function sendByCard($card, $amount, $currency)
    {
        try {
            $success = Charge::create(['card' => $card, 'amount' => $amount, 'currency' => $currency]);
        } catch(Card $e) {
            $success = $e->getJsonBody()['error'];
        }

        return $success->id;
    }

    /**
     * @param string $customer
     * @param integer $amount
     * @param string $currency
     * @return null|string
     */
    public function sendByCustomer($customer, $amount, $currency)
    {
        try {
            $success = Charge::create(['customer' => $customer, 'amount' => $amount, 'currency' => $currency]);
        } catch(Card $e) {
            $success = $e->getJsonBody()['error'];
        }

        return $success->id;
    }
}
