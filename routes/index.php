<?php

use App\Gateway;
use App\Blacklist;
use App\Session;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

use Proxy\Proxy;
use Proxy\Adapter\Guzzle\GuzzleAdapter;
use Proxy\Filter\RemoveEncodingFilter;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\SapiEmitter;
use GuzzleHttp\Client;

use Exception\NotFoundException;
use Exception\ForbiddenException;


$app->any("/[{path:.*}]", function ($request, $response, $arguments) {

    $path = explode("/", $arguments["path"])[0];
    $gateway = $this->spot->mapper("App\Gateway")->first(["path" => $path]);
    $endpoint = $gateway->endpoint;

    if ($path == "login" || $path == "register" || $path="teacher") {
        $body = $request->getParsedBody();
        $devid = isset($body["device_id"]) ? $body["device_id"] : null;
        $device_name = isset($body["device_name"]) ? $body["device_name"] : null;
        $latitude   = isset($body["latitude"]) ? $body["latitude"] : null;
        $longitude   = isset($body["longitude"]) ? $body["longitude"] : null;

        $globalRequest = ServerRequestFactory::fromGlobals();
        // $guzzle = new Client(["debug" => fopen('php://stderr', 'w'), "expect" => false]);
        $guzzle = new Client(["expect" => false]);
        // $guzzle = new Client();    
        $proxy = new Proxy(new GuzzleAdapter($guzzle));
        $one = microtime(true);
    
        $proxy->filter(new RemoveEncodingFilter());
        $proxyResponse = $proxy->forward($globalRequest)->to($endpoint);
        $two = microtime(true);
        $lapsed = $two - $one;
        $this->logger->addInfo("Time lapsed for guzzle: " . $lapsed);
        $responseCode = $proxyResponse->getStatusCode(); 
        $responseBody = $proxyResponse->getBody();
        $decodedResponse = json_decode($responseBody);
        $responseToken = $decodedResponse->token;
        $decodedToken = JWT::decode($responseToken, getenv("JWT_SECRET"), ["HS256"]);
        $jti = $decodedToken->jti;

        $dataSession = $this->spot->mapper("App\Session")
                        ->all()
                        ->where(["username" => $body["email"]]);
        $totalCopy = $dataSession->count();

        if ($totalCopy < getenv("TOTAL_COPY") ) {
            $newSession = new Session([
                "username" => $body["email"],
                "jti" => $jti,
                "devid" => $devid,
                "device_name" => $device_name,
                "latitude"   => $latitude,
                "longitude"   => $longitude
            ]);
            $this->spot->mapper("App\Session")->save($newSession); 
        } else {
            $newSession = $this->spot->mapper("App\Session")->all()->order(["last_login_at" => "ASC"])->first(["username" => $body["email"]]); 
            $newSession->jti = $jti; 
            $newSession->devid = $devid;
            $newSession->device_name = $device_name;
            $newSession->latitude = $latitude;
            $newSession->longitude = $longitude;
            $this->spot->mapper("App\Session")->save($newSession);
        }
        // $this->spot->mapper("App\Session")->save($dataSession);
        return $response->withStatus($responseCode)
        ->withHeader("Content-Type", "application/json")
        ->write((string) $responseBody);                
    
    } 
    $username = $this->token->decoded->sub;
    $jti = $this->token->decoded->jti;

    if (false === $dataSession = $this->spot->mapper("App\Session")->first(["username" => $username, "jti" => $jti])) {
        throw new ForbiddenException("Newer device already login, please re-login", 403);
    }
    
    $globalRequest = ServerRequestFactory::fromGlobals();
    // $guzzle = new Client(["debug" => fopen('php://stderr', 'w'), "expect" => false]);
    $guzzle = new Client(["expect" => false]);
    // $guzzle = new Client();    
    $proxy = new Proxy(new GuzzleAdapter($guzzle));
    $one = microtime(true);

    $proxy->filter(new RemoveEncodingFilter());
    $proxyResponse = $proxy->forward($globalRequest)->to($endpoint);
    $two = microtime(true);
    $lapsed = $two - $one;
    $this->logger->addInfo("Time lapsed for guzzle:" . $lapsed);
    $resCode = $proxyResponse->getStatusCode(); 
    $body = $proxyResponse->getBody();
    return $response->withStatus($resCode)
    ->withHeader("Content-Type", "application/json")
    ->write((string) $body);                

});
