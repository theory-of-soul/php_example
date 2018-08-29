<?php

namespace Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Models\Application;

class ChatapiController {
    
    public function indexAction(Application $app, $path) {
        
        // '/web/assets/img/upload'
        $avatarPath = __DIR__ . '/../..' . $app['config']['uploadImgPath'] . '/avatar/';
        $avatarUrl = str_replace('/web/','/',$app['config']['uploadImgPath'] . '/avatar/');

        $res = $app->getMysql()->fetchAll('SELECT `id`,`username`,`username` AS nick,`firstname`,`lastname`,`country` '
                                          . ' FROM `user` WHERE `is_active`=1 AND `phpsid`="'.stripcslashes($path).'"');
        if(!isset($res[0])) return new JsonResponse(['success' => false ]);
        if(strlen($res[0]['firstname'])>0 || strlen($res[0]['lastname'])>0)
        {    
            $res[0]['nick'] = trim($res[0]['firstname'].' '.$res[0]['lastname']);
        }
        $res[0]['avatar'] = file_exists($avatarPath . $res[0]['id'] . '.jpg') ?  $avatarUrl . $res[0]['id'] . '.jpg' : '';
        return new JsonResponse($res[0]);
    }
    
}

