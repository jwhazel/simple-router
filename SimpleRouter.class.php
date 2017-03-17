<?php

/**
 * SimpleRouter
 *
 * A tiny php router influenced heavily by Express.js routing syntax
 *
 * @author		Jesse Hazel
 * @version     1.0.0
 */


/***************************************************************************************/

class SimpleRouter {

	/**
	 * @var array Array of all routes, parameters, and queries
	 */
	protected $route = [];


	/**
	 * @var string Contains the request method from the client
	 */
	protected $requestMethod = '';


	/**
	 * @var array Array of allowed request methods
	 */
	protected $allowedMethods = ['get', 'post', 'put', 'patch', 'delete'];


	/**
	  * Create router 
	  *
	  * @param string $basePath User defined base directory if app lives in a sub directory
	  * @param array $allowedMethods Array of user defined allowed methods
	  */
	public function __construct($basePath = "", $allowedMethods = null){

		//Capture original route removing the basePath if specified
		$routeOriginal = (substr($_SERVER['REQUEST_URI'], 0, strlen($basePath)) == $basePath) ?  substr($_SERVER['REQUEST_URI'], strlen($basePath)) : $_SERVER['REQUEST_URI'];


		//If query strings are present, seperate them into their own array
		$routeQueryPos = strpos($routeOriginal, "?");
		if($routeQueryPos !== FALSE){
			$routeQuerylessPattern = substr($routeOriginal, 0, $routeQueryPos);
			parse_str(substr($routeOriginal, ($routeQueryPos+1)), $routeQueries);
		}
		else{
			$routeQuerylessPattern = $routeOriginal;
			$routeQueries = [];
		}


		//Initialize the route object
		$this->route = [ 
			'original' => $routeOriginal,
			'path' => $routeQuerylessPattern,
			'query' => $routeQueries,
			'parsed' => split("/", $routeQuerylessPattern),
			'params' => []
		];


		//Let allowed request methods be overwritten during class instantiation
		$this->requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
		if(!is_null($allowedMethods)){
			$this->allowedMethods = array_map('strtolower', $allowedMethods);
			if(!in_array($this->requestMethod, $this->allowedMethods)){
				$this->status(405)->json(["error"=> 405, "msg"=> "method not allowed"])->end();
			}
		} 
	}


	/**
	 * Magic method that matches called method to http request type for syntactical sugar
	 *
	 * @param string $methodName the type of http request being made called as a method
	 * @param array $args [0] -> pattern to match, [1] -> callback to be executed
	 */
	public function __call($methodName, $args){
		if ($methodName == $this->requestMethod && $this->matchRoutes($args[0])){
			$this->resolve($args[1]);
		}
	}



	/**
	 * Resolve the pattern when matched by firing the passed in callback
	 *
	 * @param function $callback the function to be executed, $req = request data, $res = response methods
	 */
	private function resolve($callback){
		$req = [
			'originalUrl' => $this->route['original'],
			'path' => $this->route['path'],
			'params' => $this->route['params'],
			'query' => $this->route['query'],
			'headers' => getallheaders(),
			'ip' => $this->getIp(),
			'body' => $this->bodyParser()
		];

		$callback($req, [$this, response][0]);
	}



	/**
	 * Methods returned in the callback to output data to the client
	 */
	public function response(){
		$this->json($data);
		$this->send($data);
		$this->status($statusCode);
		$this->set($field, $value);
	}

	/**
	 * Return data to client as json encoded
	 *
	 * @param string $data 
	 * @param bool $endExecution (optional) halt execution after data has been sent
	 */
	public function json($data, $endExecution = FALSE){
		header('Content-Type: application/json');
		echo json_encode($data);
		return $this;
	}


	/**
	 * Return data to client as html/text
	 *
	 * @param string $data 
	 * @param bool $endExecution (optional) halt execution after data has been sent
	 */
	public function send($data, $endExecution = FALSE){
		echo $data;
		return $this;
	}


	/**
	 * Set the HTTP response code
	 *
	 * @param number $statusCode 
	 */
	public function status($statusCode){
		http_response_code($statusCode);
		return $this;
	}


	/**
	 * Set arbitrary headers for the response
	 *
	 * @param string $field The header to be set
	 * @param string $value The value of the header
	 */
	public function set($field, $value){
		header($field . ": " . $value);
		return $this;
	}


	/**
	 * Set arbitrary headers for the response
	 *
	 * @param string $field The header to be set
	 * @param string $value The value of the header
	 */
	public function end(){
		die();
	}


	/**
	 * Match the routes with the pattern
	 *
	 * @param string $pattern the named pattern
	 */
	private function matchRoutes($pattern){
		$splitPattern = split("/", $pattern);

		//Quick check to see if number of parameters match
		if(count($splitPattern) !== count($this->route['parsed'])){
			return FALSE;
		}

		$routeParams = [];
		foreach($splitPattern as $key=>$param){
			if(strpos($param, ":") !== FALSE){
				$routeParams[ltrim($param, ":")] = $this->route['parsed'][$key];
			}
			else{
				if($this->route['parsed'][$key] !== $param){
					return false;
				}
			}
		}

		$this->route['params'] = $routeParams;
		return TRUE;
	}



	private function bodyParser(){
		$parsedBody = file_get_contents('php://input');
		return $parsedBody;
		/*
		if($body != ""){
			$isBodyJson = json_decode($parsedBody, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $isBodyJson;
			}
			else{
				return $parsedBody;
			}
		}
		*/
	}



	/**
	 * Get the IP address for the request, use the left most entry in x-Forwarded-For header
	 */
	private function getIP(){
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		$ipList = split(",", $ip);
		return $ipList[0];
	}


	
}


?>