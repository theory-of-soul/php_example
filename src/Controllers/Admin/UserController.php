<?php

namespace Controllers\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Models\Application;

class UserController {
    public function listAction(Application $app) {
        $items = $app->getObjectCache()->getUserWrapper()->getAllWithSubsctiption();

        return new JsonResponse([
            'items' => $items,
            'success' => true
        ]);
    }

    public function saveAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);
        $data = $request['data'];
        $result = $app->getObjectCache()->getUserWrapper()->save($data);

        return new JsonResponse($result);
    }

    public function deleteAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);
        $result = $app->getObjectCache()->getUserWrapper()->remove($request['id']);

        return new JsonResponse($result);
    }
}