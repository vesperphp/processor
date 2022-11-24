<?php

namespace Route;

class Request{

    public static function type(){

        if(!empty($_POST)){
            return ['type'=>'POST', 'body'=>$_GET['path'], 'post'=> $_POST];
        }

        if(!empty($_GET)){
            return ['type'=>'GET', 'body'=>addslashes($_GET['path']), 'post'=>[]];
        }

        return false;

    }

    public static function get(){

        if(!empty($_GET)){
            return addslashes($_GET['path']);
        }

        return '/';

    }

    public static function post(){

        if(!empty($_POST)){
            return $_POST;
        }

        return false;

    }

    public static function set(){

        if(!empty($_POST)){
            return 'POST';
        }

        return 'GET';

    }

}