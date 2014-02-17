<?php

class BaseController {


    public function index(){

    }

    private function getContent($file, $params = array())
    {
        ob_start();

        foreach($params as $key => $value){
            $$key = $value;
        }

        include('views/'.$file);

        $template = ob_get_contents();

        ob_end_clean();

        return $template;
    }

}