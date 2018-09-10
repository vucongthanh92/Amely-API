<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);
class ExampleMiddleware
{
	public function __construct($container) {
        $this->container = $container;
        $this->db = $container['db'];
    }

    public function __invoke($request, $response, $next)
    {
    	$user_token_ignore = ["appupdate", "register", "authtoken", "activation", "resize", "download_file", "forgot_password", "services"];

    	$route = $request->getAttribute('route');
    	$name = $route->getName();
	    $response = $next($request, $response);

    	if (in_array($name, $user_token_ignore)) {
    		return $response;
    	} else {
		    $getParams = $request->getQueryParams();
		    $token = $getParams['user_token'];
    		if (checkToken($this->db, $token)) {
    			return $response;	
    		}
    		return response(false);
    	}
    }
}
$app->add( new ExampleMiddleware($container) );
