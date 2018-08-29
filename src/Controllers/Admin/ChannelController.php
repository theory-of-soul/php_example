<?php

namespace Controllers\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Models\Application;

class ChannelController {
    public function saveAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);
        $data = $request['data'];
        $result = $app->getObjectCache()->getChannelWrapper()->save($data);

        return new JsonResponse($result);
    }

    public function deleteAction(Application $app) {
        $request = json_decode($app->getRequest()->getContent(), true);
        $result = $app->getObjectCache()->getChannelWrapper()->remove($request['id']);

        return new JsonResponse($result);
    }
    
    public function saveLogoAction(Application $app) {
        $id = intval($_POST['id']);
        if($id)             
        {    
          $path = $_SERVER['DOCUMENT_ROOT'] . '/channelicon/' . $id . '.png';
          $rootDir = str_replace('public_html','deploy/',$_SERVER['DOCUMENT_ROOT']);
          if(is_dir($rootDir))
          {
            $num = 0;
            $d = dir($rootDir);
            while (false !== ($entry = $d->read())) {
                $n =  intval($entry);
                if($n>$num) $num = $n; 
            }
            $d->close();
            if($num) $path = $rootDir.$num.'/web/channelicon/'. $id . '.png'; 
          }


          $cn = @file_get_contents($_FILES['logo']['tmp_name']);
          if($cn) 
              file_put_contents($path, $cn);
          else
              unlink($path);
        }
        return '<script>window.location.href="/admin/events'.($id?'/channel/'.$id:'').'"</script>';
    }
    
   
}