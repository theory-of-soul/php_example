<?php

namespace Controllers\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Models\Application;

class BannerController {
    public function saveAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);
        $result = $app->getObjectCache()->getBannerWrapper()->save($request);
        return new JsonResponse($result);
    }

    public function deleteAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);
        $result = $app->getObjectCache()->getBannerWrapper()->remove($request['id']);
        return new JsonResponse($result);
    }
}