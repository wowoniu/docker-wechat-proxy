<?php


class WebsocketService{

    //广播
    static public function broadCast($server,$msg=""){
        $clientLists=$server->getClientList();
        if(count($clientLists)==0)return false;
        foreach($clientLists as $fd){
            //$clientInfo=$server->getClientInfo($fd);
            $server->push($fd,$msg);
        }
        return true;
    }
}