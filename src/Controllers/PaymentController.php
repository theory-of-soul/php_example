<?php

namespace Controllers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Models\Application;

class PaymentController {
    public function createPaymentAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);

        $user = null;
        $token = $app['security']->getToken();
        if($token) {
            $user = $token->getUser();
        }

        if(!$user) {
            $response = ['success' => false, 'message' => 'payment.errors.user_not_found'];
        } else if(!isset($request['paymentOptions'])) {
            $response = ['success' => false, 'message' => 'payment.errors.empty_payment_options'];
        } else {
            $response = $app['payment']->make($request['paymentOptions'], $user);
        }

        return new JsonResponse($response);
    }

    public function callbackPayseraAction(Application $app) {
        $app['payment']->confirm('paysera', [
            'data' => $app->getRequest()->get('data'),
            'ss1' => $app->getRequest()->get('ss1')
        ]);

        $response = new Response();
        $response->setContent('OK');
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
}