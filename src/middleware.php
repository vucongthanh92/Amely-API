<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);
class AuthMiddleware
{
	public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function __invoke($request, $response, $next)
    {
    	$user_token_ignore = ["appupdate", "register", "authtoken", "activation", "resize", "download_file", "forgot_password", "services", "payment_response"];

    	$route = $request->getAttribute('route');
        if (!$route) return responseError("token_error");
        $name = $route->getName();
        if (in_array($name, $user_token_ignore)) {
            return $next($request, $response);
        } else {
		    $params = $request->getQueryParams();
            if (!array_key_exists("user_token", $params)) return responseError("token_error");
            $tokenService = TokenService::getInstance();

            if ($tokenService->checkToken($params['user_token'])) {
    			return $next($request, $response);
    		}
    		return responseError("token_error");
    	}
    }
}
$app->add( new AuthMiddleware() );
