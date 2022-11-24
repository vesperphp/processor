<?php

namespace Route;

use Logger\Log;
use Config\Config;
use Route\Request;
use Frontier\Frontier;
use Frontier\Service\Hook;


class Route{
    
    public $request;
    public $routes;


    public function __construct(){
        
        $this->request = Request::type();
        $this->routes = $_SESSION["V_ROUTE"];
        
    }


    /**
     * HTTP Requests: Get
     * This loads a page with the 
     * normal _GET request.
     */
    public static function get($path, $controller, $method, $ware=[]){

        if(isset($_SESSION['V_ROUTE'])){
            $routes = $_SESSION['V_ROUTE'];
        }
        
        $routes['GET'][$path]['controller'] = $controller;
        $routes['GET'][$path]['method'] = $method;
        $routes['GET'][$path]['ware'] = $ware;

        $_SESSION['V_ROUTE'] = $routes;

        //return $this;

    }


    /**
     * HTTP Requests: Post
     * This posts a page with the 
     * normal _POST request.
     */
    public static function post($path, $controller, $method, $ware=[]){

        if(isset($_SESSION['V_ROUTE'])){
            $routes = $_SESSION['V_ROUTE'];
        }
        
        $routes['POST'][$path]['controller'] = $controller;
        $routes['POST'][$path]['method'] = $method;
        $routes['POST'][$path]['ware'] = $ware;

        $_SESSION['V_ROUTE'] = $routes;

        //return $this;

    }



    /**
     * This method paints
     * view to the index.php file.
     */
    public function paint(){

        /**
         * Compare the path to the
         * routes set in the init/routes file.
         * 
         * Values in path:
         * - controller
         * - method
         * - vars (variables from the uri string)
         * - ware (an array of limiters and modifiers)
         */

        $path = $this->prepare();
 
        /**
         * Verify if the page actually exists
         * within the set of routes. If not, 
         * forward to the 404 page.
         */

        if($path==NULL){
           
            Frontier::error(404);
            exit;
        }

        /**
         * Next step it to determine if we can 
         * can continue by checking the Ware
         * loop attached to the route.
         */

        if(!empty($path['ware'])){
            foreach($path['ware'] as $limiters){
                $this->ware($limiters);
            }
        }

        /** 
         * If the class exists and Ware doesn't 
         * stop the process, then continue loading
         * the controller and method.
         */
        
        if(class_exists($path['controller'])){
            

            header("HTTP/1.0 200 OK");

            /**
             * Fetch the Controller
             */
            $paint = new $path['controller'];

            /**
             * Add the path variables to the model
             */
            if(property_exists($paint, "path") && !empty($path['vars'])){
                $paint->path($path['vars']);
            }

            $method = $path['method'];
            $paint->$method();

        }else{
            Frontier::error(404);

        }
                

    }



    /**
     * Prepare the set routes to compare
     * with the path from the request
     */

    public function prepare(){

        $type = Request::set();
        $routes = $this->routes[$type];
        $raw = rtrim(Request::get(),'/');
        $path = explode('/', $raw);
        $pathCount = count($path);
        $all = [];

        // Exception 1:
        // If the path array is empty, then return the '/' homepage controller

        if(empty($path)){ return $routes['/']; }

        // Else, continue...


        foreach($routes as $route => $object){

            // check for middleware
            if(!isset($object['ware'])){ $object['ware'] = []; }

            $parts = explode('/',rtrim($route,'/'));
            $partCount = count($parts);

            // If the part and path counts are equal..
            if($partCount == $pathCount){
            
                $i = 0;
                $var = [];

                // Check if each part has a dynamic variable in it...
                foreach($parts as $part => $content){
                    
                    
                    // If the content is a variable in the uri...
                    if(str_contains($content,"{") && str_contains($content,"}") ){

                        $str = preg_replace('~\{\s*(.+?)\s*\}~is', '$1', $content);

                        $newPath[$i] = $path[$i];
                        $var[$str] = $path[$i];

                    // Slse just take the regular var..
                    }else{
                        $newPath[$i] = $content;
                    }

                    $i++;
                }

                /**
                 * Set up the pagination
                 * array key for easy access.
                 */
                
                if(!isset($var['paginate'])){ $var['paginate'] = 1; }
                $var['paginate'] = intval( $var['paginate'] );

    
                // If the comparable route is assembled, implode it..
                $newRoute = implode('/',$newPath);

                // Fill the array with data..
                $all[$newRoute] = [
                    'controller'=>$object['controller'],
                    'method'=>$object['method'],
                    'vars'=>$var,
                    'routes'=>$route,
                    'ware'=>$object['ware'] 
                    
                    
                ];

               
            }

  

        }
        
        /**
         * If the route does not exist
         * return a null value.
         */

        if(!isset($all[$raw])){ return NULL; }

        /**
         * Define and
         * return.
         */

        define('V_PATH', [
            'uri' => Config::get('site/uri'), 
            'path' => $all[$raw]['routes'], 
            'set' => $raw, 
            'vars' => $all[$raw]['vars']
            ]
        );
        return $all[$raw];

        
    }


    /**
     * Add the limiting Ware array to the mix, 
     */

    public function ware($limiter){

        //dump($limiter,'limites in ware');

        if(!isset($limiter[2])){ $limiter[2] = ''; }

        $controller = $limiter[0];
        $method = $limiter[1];
        $params = $limiter[2];

        if(class_exists($controller)){
        
            //header("HTTP/1.0 200 OK");

            /**
             * Fetch the Class
             */

            $ware = new $controller;

            /**
             * Execute the model
             * with the params attaches
             */

            $policy = $ware->$method($params);

            /**
             * Check the return value.
             * This will automatically send you to the 404 page.
             */

            if($policy==false){

                Log::to(['Shield has dodged an arrow.'],'shield');
                die("Shield has dodged an arrow.");

            }


        }else{

            
            Log::to(['This Shield class does not exist.' => $controller],'shield');
            Frontier::error(404);

            

        }

    }

    /**
     * If the route doesn't exist
     * then return a 404.
     */

    public function error($code=404){


        Frontier::error(404);
        Log::to(['Error' => $code],'frontier');

    }

    /**
     * Close and reset variables
     * where needed..
     */

     public function close(){

        /**
         * Clear the tag cache
         * so they only work on
         * this paint.
         */

        Hook::clear();

        Log::to(['user_ip'=>$_SERVER['REMOTE_ADDR'], 'user_agent' => $_SERVER['HTTP_USER_AGENT']],'visits');
        //Speed::log();
        
     }


}