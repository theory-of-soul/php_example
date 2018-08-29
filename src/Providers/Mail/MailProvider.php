<?php

namespace Providers\Mail;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\SwiftmailerServiceProvider;

class MailProvider implements ServiceProviderInterface {
    private $app;
    private $from = 'noreply@tv-sport.pro';

    private function getTransporter() {
        return \Swift_SmtpTransport::newInstance($this->app['config']['mail']['host'], $this->app['config']['mail']['port'])
        ->setUsername($this->app['config']['mail']['email'])
        ->setPassword($this->app['config']['mail']['password']);
    }

    public function sendRaw(\Swift_Message $message)
    {
        $mailer = \Swift_Mailer::newInstance( $this->getTransporter() );
        return $mailer->send($message);
    }

    public function sendMail($mailTo, $subject, $text, $mailFrom = null) {
        if(!$mailFrom) {
            $mailFrom = $this->from;
        }

        $mailer = \Swift_Mailer::newInstance( $this->getTransporter() );
        $message = \Swift_Message::newInstance()->setSubject($subject)->setFrom([$mailFrom])->setBody($text);

        if(is_array($mailTo)) {
            $message->setBcc($mailTo);
        } else  {
            $message->setTo($mailTo);
        }

        return $mailer->send($message);
    }

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app) {
        $app['mail'] = $this;
        $this->app = $app;
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app) {
        $this->app->register(new SwiftmailerServiceProvider());
    }
}
