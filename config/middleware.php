<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016-2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */
use App\Token;
use App\User;

use Slim\Middleware\JwtAuthentication;
use Slim\Middleware\HttpBasicAuthentication;
use Tuupola\Middleware\Cors;
use Gofabian\Negotiation\NegotiationMiddleware;
use Micheh\Cache\CacheUtil;

$container = $app->getContainer();

// $container["HttpBasicAuthentication"] = function ($container) {
//     return new HttpBasicAuthentication([
//         "path" => "/token",
//         "relaxed" => ["localhost", "qnq.jwt.api"],
//         "users" => [
//             "test" => "test"
//         ]
//     ]);
// };

$container["token"] = function ($container) {
    return new Token;
};

$container["authManager"] = function ($container) {
    return Yii::$app->authManager;
};

$container["JwtAuthentication"] = function ($container) {
    return new JwtAuthentication([
        "path" => "/",
        "passthrough" => ["/login", "/register", "/forgot", "/refresh", "/teacher"],
        "secret" => getenv("JWT_SECRET"),
        "logger" => $container["logger"],
        "relaxed" => explode(",", getenv("RELAXED")),
        "error" => function ($request, $response, $arguments) {
            $data["status"] = "error";
            $data["message"] = $arguments["message"];
            return $response
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        },
        "callback" => function ($request, $response, $arguments) use ($container) {
            $container["token"]->hydrate($arguments["decoded"]);
        }
    ]);
};

$container["Cors"] = function ($container) {
    return new Cors([
        "logger" => $container["logger"],
        "origin" => ["*"],
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since", "Content-Type", "Access-Control-Allow-Origin"],
        "headers.expose" => ["Authorization", "Etag"],
        "credentials" => true,
        "cache" => 60,
        "error" => function ($request, $response, $arguments) {
            $data["status"] = "error";
            $data["message"] = $arguments["message"];
            return $response
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    ]);
};


$container["Negotiation"] = function ($container) {
    return new NegotiationMiddleware([
        "accept" => ["application/json"]
    ]);
};

//$app->add("HttpBasicAuthentication");
$app->add("JwtAuthentication");
$app->add("Cors");
$app->add("Negotiation");

$container["cache"] = function ($container) {
    return new CacheUtil;
};
