<?php

namespace Corbado;

use Corbado\Classes\Helper;
use Corbado\Exceptions\Standard;
use Corbado\Classes\Assert;

class Webhook
{
    /**
     * Username for basic authentication (needs to be configured at Corbado developer panel (https://app.corbado.com))
     *
     * @var string
     */
    private string $username;

    /**
     * Username for basic authentication (needs to be configured at Corbado developer panel (https://app.corbado.com))
     *
     * @var string
     */
    private string $password;

    /**
     * @var bool
     */
    private bool $automaticAuthenticationHandling = true;

    /**
     * @var bool
     */
    private bool $authenticated = false;

    const ACTION_AUTH_METHODS = 'auth_methods';
    const ACTION_PASSWORD_VERIFY = 'password_verify';
    const STANDARD_FIELDS = ['projectID', 'action', 'requestID'];

    /**
     * Constructor
     *
     * @param string $username
     * @param string $password
     * @throws Exceptions\Assert
     */
    public function __construct(string $username, string $password)
    {
        Assert::stringNotEmpty($username);
        Assert::stringNotEmpty($password);

        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Disables automatic authentication handling
     *
     * If you disable authentication you have to handle
     * basic authentication yourself!
     *
     * @return void
     */
    public function disableAutomaticAuthenticationHandling() : void
    {
        $this->automaticAuthenticationHandling = false;
    }

    /**
     * Checks authentication (provided username and password)
     *
     * @return bool
     */
    public function checkAuthentication() : bool {
        if (empty($_SERVER['PHP_AUTH_USER'])) {
            return false;
        }

        if (empty($_SERVER['PHP_AUTH_PW'])) {
            return false;
        }

        if ($_SERVER['PHP_AUTH_USER'] === $this->username && $_SERVER['PHP_AUTH_PW'] ===  $this->password) {
            return true;
        }

        return false;
    }

    /**
     * Sends unauthorized response in case authentication failed
     *
     * @param bool $exit If true the methods exists, default is true
     * @return void
     */
    public function sendUnauthorizedResponse(bool $exit = true) : void
    {
        header('WWW-Authenticate: Basic realm="Webhook"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Unauthorized.';

        if ($exit) {
            exit(0);
        }
    }

    /**
     * Handles authentication
     *
     * @return void
     * @throws Standard
     */
    public function handleAuthentication() : void
    {
        if ($this->authenticated) {
            throw new Standard("Already authenticated, something is wrong");
        }

        if ($this->checkAuthentication() === true) {
            $this->authenticated = true;

            return;
        }

        $this->sendUnauthorizedResponse();
    }

    /**
     * Checks automatic authentication
     *
     * @return void
     * @throws Standard
     */
    private function checkAutomaticAuthentication() : void
    {
        if ($this->automaticAuthenticationHandling === false) {
            return;
        }

        if ($this->authenticated === true) {
            return;
        }

        throw new Standard("Missing authentication, call handleAuthentication() first");
    }

    /**
     * Checks if request method is POST (the only supported method from Corbado)
     *
     * @return bool
     * @throws Standard
     */
    public function isPost() : bool
    {
        $this->checkAutomaticAuthentication();

        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Returns webhook action (by reading the header field X-Corbado-Action)
     *
     * @return string
     * @throws Standard
     */
    public function getAction() : string
    {
        $this->checkAutomaticAuthentication();

        if (empty($_SERVER['HTTP_X_CORBADO_ACTION'])) {
            throw new Standard('Missing action header (X-CORBADO-ACTION)');
        }

        switch ($_SERVER['HTTP_X_CORBADO_ACTION']) {
            case 'authMethods':
                return self::ACTION_AUTH_METHODS;

            case 'passwordVerify':
                return self::ACTION_PASSWORD_VERIFY;

            default:
                throw new Standard('Invalid action ("' . $_SERVER['HTTP_X_CORBADO_ACTION'] . '")');
        }
    }

    /**
     * Returns auth methods request model
     *
     * @return Classes\Models\AuthMethodsRequest
     * @throws Exceptions\Assert
     * @throws Standard
     */
    public function getAuthMethodsRequest() : Classes\Models\AuthMethodsRequest
    {
        $this->checkAutomaticAuthentication();

        $body = $this->getRequestBody();
        $data = Helper::jsonDecode($body);
        Assert::arrayKeysExist($data, self::STANDARD_FIELDS);
        Assert::arrayKeysExist($data['data'], ['username']);

        $dataRequest = new Classes\Models\AuthMethodsDataRequest();
        $dataRequest->username = $data['data']['username'];

        $request = new Classes\Models\AuthMethodsRequest();
        $request->projectID = $data['projectID'];
        $request->action = self::ACTION_AUTH_METHODS;
        $request->requestID = $data['requestID'];
        $request->data = $dataRequest;

        return $request;
    }

    /**
     * Sends auth methods response
     *
     * @param string $status
     * @param bool $exit
     * @return void
     * @throws Exceptions\Assert
     * @throws Standard
     */
    public function sendAuthMethodsResponse(string $status, bool $exit = true) : void
    {
        Assert::stringEquals($status, ['exists', 'not_exists', 'blocked']);

        $this->checkAutomaticAuthentication();

        $dataResponse = new Classes\Models\AuthMethodsDataResponse();
        $dataResponse->status = $status;

        $response = new Classes\Models\AuthMethodsResponse();
        $response->data = $dataResponse;

        $this->sendResponse($response);

        if ($exit) {
            exit(0);
        }
    }

    /**
     * Returns password verify request model
     *
     * @return Classes\Models\PasswordVerifyRequest
     * @throws Exceptions\Assert
     * @throws Standard
     */
    public function getPasswordVerifyRequest() : Classes\Models\PasswordVerifyRequest
    {
        $this->checkAutomaticAuthentication();

        $body = $this->getRequestBody();
        $data = Helper::jsonDecode($body);
        Assert::arrayKeysExist($data, self::STANDARD_FIELDS);
        Assert::arrayKeysExist($data['data'], ['username', 'password']);

        $dataRequest = new Classes\Models\PasswordVerifyDataRequest();
        $dataRequest->username = $data['data']['username'];
        $dataRequest->password = $data['data']['password'];

        $request = new Classes\Models\PasswordVerifyRequest();
        $request->projectID = $data['projectID'];
        $request->action = self::ACTION_AUTH_METHODS;
        $request->requestID = $data['requestID'];
        $request->data = $dataRequest;

        return $request;
    }

    /**
     * Sends password verify response
     *
     * @param bool $success
     * @param bool $exit
     * @return void
     * @throws Standard
     */
    public function sendPasswordVerifyResponse(bool $success, bool $exit = true) : void
    {
        $this->checkAutomaticAuthentication();

        $dataResponse = new Classes\Models\PasswordVerifyDataResponse();
        $dataResponse->success = $success;

        $response = new Classes\Models\PasswordVerifyResponse();
        $response->data = $dataResponse;

        $this->sendResponse($response);

        if ($exit) {
            exit(0);
        }
    }

    /**
     * Returns request body
     *
     * @return string
     * @throws Standard
     */
    private function getRequestBody() : string {
        $body = file_get_contents('php://input');
        if ($body === false) {
            throw new Standard('Could not read request body (POST request)');
        }

        return $body;
    }

    /**
     * Sends response
     *
     * @param $response
     * @return void
     * @throws Standard
     */
    private function sendResponse($response) : void {
        header('Content-Type: application/json; charset=utf-8');
        echo Helper::jsonEncode($response);
    }

}
