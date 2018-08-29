<?php
namespace Models;

use Models\Application;

class Subscription extends AbstractModel {
    protected $id;
    protected $user_id;
    protected $plan_id;
    protected $transaction_id;
    protected $date_from;
    protected $date_to;

    public function getWrapper() {
        return $this->app->getObjectCache()->getSubscriptionWrapper();
    }

    public function isActive() {
        $timestamp = time();
        return strtotime($this->date_from) < $timestamp && strtotime($this->date_to) > $timestamp;
    }
}