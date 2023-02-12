<?php

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;

final class SystemTest extends TestCase {
    private const URL = 'http://localhost:8000/simple.php';
    private const AUTH = ['webhookUsername', 'webhookPassword'];
    private const ACTION_HEADER = 'X-CORBADO-ACTION';
    private static int $pid;

    public static function setUpBeforeClass(): void
    {
        exec('php -S localhost:8000 -t examples/ > /dev/null 2>&1 & echo $!', $output);
        self::$pid = (int)$output[0];

        usleep(100000);
    }

    public static function tearDownAfterClass(): void
    {
        exec('kill ' . self::$pid);
    }

    private function createClient(): Client
    {
        return new Client(['http_errors' => false]);
    }

    /**
     * @param string $username
     * @return array<mixed>
     */
    private function createAuthMethodsRequest(string $username): array
    {
        return [
            'id' => 'who-6098645098690450945',
            'projectID' => 'pro-1',
            'action' => 'authMethods',
            'data' => [
                'username' => $username,
            ],
        ];
    }

    /**
     * @param string $password
     * @return array<mixed>
     */
    private function createPasswordVerifyRequest(string $password): array
    {
        return [
            'id' => 'who-6098645098690450945',
            'projectID' => 'pro-1',
            'action' => 'passwordVerify',
            'data' => [
                'username' => 'existing@existing.com',
                'password' => $password,
            ],
        ];
    }

    public function testAuthenticationMissing(): void
    {
        $client = $this->createClient();
        $response = $client->post( self::URL);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testAuthenticationMissingUsername(): void
    {
        $client = $this->createClient();
        $response = $client->post( self::URL, ['auth' => ['', 'webhookPassword']]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testAuthenticationMissingPassword(): void
    {
        $client = $this->createClient();
        $response = $client->post( self::URL, ['auth' => ['webhookUsername', '']]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testMethodInvalid(): void
    {
        $client = $this->createClient();
        $response = $client->get(self::URL, ['auth' => self::AUTH]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Only POST is allowed', $response->getBody());
    }

    public function testActionHeaderMissing(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Missing action header (' . self::ACTION_HEADER . ')', $response->getBody());
    }

    public function testActionHeaderInvalid(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'invalid']]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Invalid action ("invalid")', $response->getBody());
    }

    public function testAuthMethodsEmptyRequest(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'authMethods']]);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testAuthMethodsInvalidRequest(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'authMethods'], RequestOptions::BODY => '[5, 6']);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('json_decode() failed', $response->getBody());
    }

    public function testAuthMethodsMissingStandardFields(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'authMethods'], RequestOptions::JSON => []]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Assert failed: Given array has no key "id"', $response->getBody());
    }

    public function testAuthMethodsNotExists(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'authMethods'], RequestOptions::JSON => $this->createAuthMethodsRequest('nonexisting@nonexisting.com')]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody());

        $json = \json_decode($response->getBody(), true);
        $this->assertEquals(0, json_last_error());
        $this->assertEquals('not_exists', $json['data']['status']);
    }

    public function testAuthMethodsExists(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'authMethods'], RequestOptions::JSON => $this->createAuthMethodsRequest('existing@existing.com')]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody());

        $json = \json_decode($response->getBody(), true);
        $this->assertEquals(0, json_last_error());
        $this->assertEquals('exists', $json['data']['status']);
    }

    public function testPasswordVerifyEmptyRequest(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'passwordVerify']]);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testPasswordVerifyInvalidRequest(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'passwordVerify'], RequestOptions::BODY => '[5, 6']);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('json_decode() failed', $response->getBody());
    }

    public function testPasswordVerifyMissingStandardFields(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'passwordVerify'], RequestOptions::JSON => []]);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Assert failed: Given array has no key "id"', $response->getBody());
    }

    public function testPasswordVerifyInvalid(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'passwordVerify'], RequestOptions::JSON => $this->createPasswordVerifyRequest('invalid')]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody());

        $json = \json_decode($response->getBody(), true);
        $this->assertEquals(0, json_last_error());
        $this->assertEquals(false, $json['data']['success']);
    }

    public function testPasswordVerifyValid(): void
    {
        $client = $this->createClient();
        $response = $client->post(self::URL, ['auth' => self::AUTH, 'headers' => [self::ACTION_HEADER => 'passwordVerify'], RequestOptions::JSON => $this->createPasswordVerifyRequest('supersecret')]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody());

        $json = \json_decode($response->getBody(), true);
        $this->assertEquals(0, json_last_error());
        $this->assertEquals(true, $json['data']['success']);
    }
}
