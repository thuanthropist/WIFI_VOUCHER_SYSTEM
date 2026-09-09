<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\Services\PaymentGateways\AzampayGateway;
use App\Services\PaymentGateways\ClickPesaGateway;
use App\Services\PaymentGateways\SelcomGateway;
use Illuminate\Support\Manager;

/**
 * Resolves the active PaymentGatewayContract driver from config('services.payment.default'),
 * following the same "driver manager" pattern Laravel uses for cache/session/queue.
 * Swapping gateways (or adding a new aggregator) never touches PaymentService or
 * the controller — only a new driver class + a create*Driver method here.
 */
class PaymentGatewayManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('services.payment.default', 'selcom');
    }

    public function createSelcomDriver(): PaymentGatewayContract
    {
        return new SelcomGateway($this->config->get('services.selcom', []));
    }

    public function createClickpesaDriver(): PaymentGatewayContract
    {
        return new ClickPesaGateway($this->config->get('services.clickpesa', []));
    }

    public function createAzampayDriver(): PaymentGatewayContract
    {
        return new AzampayGateway($this->config->get('services.azampay', []));
    }
}
