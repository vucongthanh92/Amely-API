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
        $permissionService = PermissionService::getInstance();

    	$user_token_ignore = ["appupdate", "register", "authtoken", "activation", "resize", "download_file", "forgot_password", "services", "payment_response", "console_offers", "console_gifts", "progressbar"];

    	$route = $request->getAttribute('route');
        if (!$route) return responseError("token_error");
        $name = $route->getName();
        $uri = $request->getUri();
        $path = $uri->getPath();
        $check_path_administrator = in_array("administrator", explode('/', $path));
        $method = $route->getMethods();

        if (in_array($name, $user_token_ignore)) {
            return $next($request, $response);
        } else {
            $params = $request->getQueryParams();
            if (!array_key_exists("user_token", $params)) return responseError("token_error");
            $tokenService = TokenService::getInstance();

            if ($tokenService->checkToken($params['user_token'])) {
                $loggedin_user = loggedin_user();
                if ($loggedin_user->rule_id == 0) {
                    return $next($request, $response);    
                }
                if ($loggedin_user->rule_id == 1) {
                    return $next($request, $response);    
                }
                $check_permission = $permissionService->checkPermission($loggedin_user->rule_id, $path, $method[0]);
                if ($check_path_administrator) {
                    if ($check_permission) {
                        return $next($request, $response);
                    }
                } else {
                    return $next($request, $response);
                }
                return responseError(ERROR_2);
    		}
    		return responseError("token_error");
    	}
    }
}
$app->add( new AuthMiddleware() );
