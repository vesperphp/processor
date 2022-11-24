<?php
namespace Foundry;


class Foundry{


    public static function paint(array $argv){

        /**
         * Find the appropriate classes
         * and methods and then run them.
         */

        if(!isset($argv[1])){
            Foundry::help();
            exit;
        }

        $command = explode(':', $argv[1]);

        if(count($command)!=2){
            Foundry::help();
            exit;
        }

        /**
         * After this point we assume that 
         * there is a call for a class and method.
         * Let's see if those exist within the
         * Foundry ecosystem.
         */

        
        if(!isset($command[0]) OR !isset($command[1])){ die("\e[31mCommand invalid.\e[39m"); }

        $controller = "Foundry\Foundry".ucfirst($command[0]);
        
        $method = $command[1];
        
        echo "\n";
        
        /**
         * Does the 
         * controller exist?
         */
        
        if(class_exists($controller)){
            $foundry = new $controller;
        }else{
            echo "controller not found: ".$controller;
            Foundry::help();
            exit;
        }
        
        /**
         * Does the method 
         * exist?
         */
        
        if(!method_exists($controller, $method)){ 
            echo "method not found";
            Foundry::help();
            exit;
        }
        
        if(isset($argv[2])){
        
            /**
             * Set method with flags.
             */
        
            $flags = str_replace(" ","",$argv[2]);
            $flags = explode("/", $flags);
            $flags = array_filter($flags);
        
            $foundry->$method($flags);
        
        }else{
        
            /**
             * Set method without flags.
             */
        
            $foundry->$method();
            
        }

        //debug($command);
        //debug($argv);

        echo "\n\n\n";

    }

    public static function help(){
        echo "Gather all the help info here \n\n\n";

    }

    

}