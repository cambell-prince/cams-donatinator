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

use Carbon\Carbon; // TODO Currently this isn't actually used.
use Stripe\Stripe;

// use ActiveRecord\Config;

$app = new Silex\Application();

// Services
/**
 * Service: PhpActiveRecord instance configuration
 * @return void
 */
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

// Service: Current DateTime in UTC. Returns an \ActiveRecord\DateTime object.
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
    $amount = $request->request->get('amount');
    $currency = $request->request->get('currency');
    $email = $token['email'];
    $data = $request->request->get('data');
    $error = '';

    // TODO put this in another function, all payment methods would do this.
    // Ensure the customer is in the database and record the payment (before the charge attempt)
    $customer = App\Model\Customer::find_by_email($email);
    if (!$customer) {
        $customer = App\Model\Customer::create(
            array(
                'dtc' => $app['now'],
                'email' => $email,
            )
        );
    }
    $card = $token['card'];
    $payment = $customer->create_payment(
        array(
            'dtc' => $app['now'],
            'amount' => $amount / 100.0,
            'currency' => $currency,
            'status' => 'pending',
            'live' => $token['livemode'],
            'card_brand' => $card['brand'],
            'card_country' => $card['country'],
            'card_last4' => $card['last4'],
            'card_exp_month' => $card['exp_month'],
            'card_exp_year' => $card['exp_year'],
            'card_cvc_check' => $card['cvc_check'] === 'pass' ? 1 : 0,
            'card_funding' => $card['funding'],
            'ip' => $_SERVER["REMOTE_ADDR"],
        )
    );

    $responseCode = 599; // Not expecting to return this
    // Make the charge via Stripe
    Stripe::setApiKey(STRIPE_API_KEY);
    try {
        $charge = \Stripe\Charge::create(array(
            "amount" => $amount,
            "currency" => $currency,
            "source" => $token['id'],
            "description" => "Donation to Hannah Prince"
        ));
        if ($charge['paid'] === true) {
            $payment->status = 'ok';
            $payment->save();
            $responseCode = 201;
        } else {
            // Log this unusual event
            $payment->message = 'stripe error: charge returned, not paid, no exception';
            $payment->status = 'fail';
            $payment->save();
            $responseCode = 598;
        }
    } catch(\Stripe\Error\Card $e) {
        // Since it's a decline, \Stripe\Error\Card will be caught
        $responseCode = 403;
        $body = $e->getJsonBody();
        $err  = $body['error'];

        $payment->status = 'declined';
        $payment->message = $err['message'];
        $payment->save();

        // print('Status is:' . $e->getHttpStatus() . "\n");
        // print('Type is:' . $err['type'] . "\n");
        // print('Code is:' . $err['code'] . "\n");
        // // param is '' in this case
        // print('Param is:' . $err['param'] . "\n");
        // print('Message is:' . $err['message'] . "\n");
    } catch (\Stripe\Error\RateLimit $e) {
        // Too many requests made to the API too quickly
        $responseCode = 429;
        $payment->status = 'fail';
        $payment->message = 'rate limit';
        $payment->save();
    } catch (\Stripe\Error\InvalidRequest $e) {
        // Invalid parameters were supplied to Stripe's API
        $responseCode = 400;
        $payment->status = 'fail';
        $payment->message = 'invalid request: ' . $e->getMessage();
        $payment->save();
    } catch (\Stripe\Error\Authentication $e) {
        // Authentication with Stripe's API failed
        // (maybe you changed API keys recently)
        $responseCode = 401;
        $payment->status = 'fail';
        $payment->message = 'invalid auth: ' . $e->getMessage();
        $payment->save();
    } catch (\Stripe\Error\ApiConnection $e) {
        // Network communication with Stripe failed
        $responseCode = 408;
        $payment->status = 'fail';
        $payment->message = 'invalid connection: ' . $e->getMessage();
        $payment->save();
    } catch (\Stripe\Error\Base $e) {
        // Display a very generic error to the user, and maybe send
        // yourself an email
        $responseCode = 400;
        $payment->status = 'fail';
        $payment->message = 'other error: ' . $e->getMessage();
        $payment->save();
    }

    $responseBody = array(
        'code' => $responseCode,
        'message' => $payment->message,
        // 'customer_id' => $customer->id,
        // 'payment_id' => $payment->id,
        // 'token' => $token,
        // 'email' => $email,
        // 'data' => $data,
    );
    return $app->json($responseBody, $responseCode);
});

// Instantiate the db, PHPActiveRecord singleton so that it gets configured before first use.
$app['db'];

$app->run();
