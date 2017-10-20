<?php
require_once('vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\JsonResponse;

$app = new Silex\Application();

// JSON decode body if necessary
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
    
});

// CORS
$app->after(function (Request $request, Response $response) {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Headers', 'Authorization,Content-Type');
});

$app->options("{anything}", function () {
    return new JsonResponse(null, 204);
})->assert("anything", ".*");

// Routes
$app->post('/api/pay', function (Request $request) use ($app) {
    $data = array(
        'token' => $request->request->get('token'),
    );

    return $app->json($data, 201);
});

$app->run();
