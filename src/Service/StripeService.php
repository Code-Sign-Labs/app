<?php
declare(strict_types=1);

namespace App\Service;

use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $client;
    public function __construct()
    {
        $this->client = new StripeClient($_ENV['STRIPE_SECRET_KEY']);
    }

    /**
     * @return StripeClient
     */
    public function getClient(): StripeClient
    {
        return $this->client;
    }

    /**
     * @param string $productId
     * @return ?Product
     */
    public function getProduct(string $productId): ?Product
    {
        try {
            return $this->client->products->retrieve($productId, []);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    /**
     * @param string $priceId
     * @return Price|null
     */
    public function getPrice(string $priceId): ?Price
    {
        try {
            return $this->client->prices->retrieve($priceId, []);
        } catch (ApiErrorException $e) {
            return null;
        }
    }
}