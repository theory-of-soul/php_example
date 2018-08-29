<?php
namespace Providers\PaymentProvider;

use Silex\ServiceProviderInterface;
use Models\Application;

class PaymentProvider implements ServiceProviderInterface {
    private $gateways;
    private $currencyList = ['usd'];

    public function make($paymentOptions, $user) {
        if(!isset($paymentOptions['type']) || !$this->getGateway($paymentOptions['type'])) {
            return ['success' => false, 'message' => 'payment.errors.unknown_type'];
        }

        if( !isset($paymentOptions['amount']) ) {
            return ['success' => false, 'message' => 'payment.errors.amount_empty'];
        }

        if( !isset($paymentOptions['currency']) ) {
            return ['success' => false, 'message' => 'payment.errors.currency_empty'];
        }

        if( !in_array($paymentOptions['currency'], $this->currencyList) ) {
            return ['success' => false, 'message' => 'payment.errors.currency_unknown'];
        }

        if(!$paymentOptions['plan_id']) {
            return ['success' => false, 'message' => 'payment.errors.plan_not_found'];
        }

        $properties = [
            'user_id' => $user->get('id'),
            'type' => $paymentOptions['type'],
            'status' => 'pending',
            'amount' => $paymentOptions['amount'],
            'currency' => $paymentOptions['currency'],
            'user_email' => $user->get('email'),
            'plan_id' => $paymentOptions['plan_id'],
        ];

        $gateway = $this->getGateway($paymentOptions['type']);
        return $gateway->makeTransaction($properties);
    }

    public function confirm($type, $request) {
        $gateway = $this->getGateway($type);
        if($gateway) {
            $gateway->confirm($request);
        }
    }

    public function register(\Silex\Application $app) {
        $app['payment'] = $this;
        $this->app = $app;
    }

    public function boot(\Silex\Application $app) {
        $this->gateways = [
            'paysera' => new Paysera($app),
        ];
    }

    private function getGateway($type) {
        return isset($this->gateways[$type]) ? $this->gateways[$type] : null;
    }
}