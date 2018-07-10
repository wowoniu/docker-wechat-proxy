<?php

Class Controller{

    protected $request;
    protected $response;
    protected $serv;
    public function __construct($request,$response,$serv){
        $this->request=$request;
        $this->response=$response;
        $this->serv=$serv;
    }

    public function display($tpl,$data=array()){
        $tplFile = $this->_getTpl($tpl);
        ob_start();
        include $tplFile;
        $html = ob_get_clean();
        $this->response->end($html);
    }

    protected function _getTpl($tpl){
        $file = './App/Views/' . $tpl.'.html';
        return $file;
    }
}