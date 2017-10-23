<?php
require_once('vendor/autoload.php');
require_once('config.php');

if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Silex\Application;
use Carbon\Carbon;

// use ActiveRecord\Config;

$app = new Silex\Application();

// Services
$app['db'] = function (Application $app) {
    \ActiveRecord\Config::initialize(function ($cfg) use ($app) {
        $cfg->set_model_directory(__DIR__ . '/Model');
        $cfg->set_connections(array(
            'development' => 'mysql://test:test@localhost/princes_hannah',
            'production' => 'mysql://' . DB_USER . ':' . DB_PASSWORD . '@localhost/' . DB_DATABASE
        ));
        $cfg->set_default_connection(DB_DEFAULT_CONNECTION);
        // The line below stops PHPActiveRecord from setting the timezone when inserting / updating
        // DATETIME fields which is not supported by the version of mySQL that I have installed.
        \ActiveRecord\Connection::$datetime_format = 'Y-m-d H:i:s';
    });
};

$app['now'] = function(Application $app) {
    return new \ActiveRecord\DateTime('now', new DateTimeZone('UTC'));

};

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

// Error handler. Logging needs to go before this when implemented
$app->error(function(\Exception $e) use ($app) {
    $data = array(
        'error' => $e->getMessage()
    );
    return $app->json($data);
});

// Routes
$app->post('/api/pay/stripe', function (Request $request) use ($app) {
    $token = $request->request->get('token');
    $email = $request->request->get('email');
    $data = $request->request->get('data');
    $error = '';

    $customer = App\Model\Customer::find_by_email($email);
    if (!$customer) {
        $customer = \App\Model\Customer::create(
            array(
                'dtc' => $app['now'],
                'email' => $email,
            )
        );
    }
    $customer->create_payment(
        array(
            'dtc' => $app['now'],
            'amount' => $data['amount'] / 100.0,
            'status' => 'pending'
        )
    );

    $data = array(
        'customer_id', $customer->id,
        'payment_id', $payment->id,
        'token' => $token,
        'email' => $email,
        'data' => $data,
    );
    return $app->json($data, 201);
});

// Instantiate the db, PHPActiveRecord singleton so that it gets configured before first use.
$app['db'];

$app->run();
