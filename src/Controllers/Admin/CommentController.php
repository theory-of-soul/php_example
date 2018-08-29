<?php

namespace Controllers\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Models\Application;

class CommentController {
    public function saveAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);
        $data = $request['data'];
        $result = $app->getObjectCache()->getCommentWrapper()->save($data);

        return new JsonResponse($result);
    }

    public function deleteAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);
        $result = $app->getObjectCache()->getCommentWrapper()->remove($request['id']);

        return new JsonResponse($result);
    }
}