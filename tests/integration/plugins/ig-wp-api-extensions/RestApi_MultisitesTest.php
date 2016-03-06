<?php

use WP_Mock\Tools\TestCase;

/**
 * Author: Martin
 * Created: 27.02.2016 16:24
 */
class RestApi_MultisitesTest extends TestCase {
	/** @var GuzzleHttp\Client */
	private $client;

	public function setUp() {
		parent::setUp();
		$this->client = new GuzzleHttp\Client([
			'base_uri' => 'http://localhost'
		]);
	}

	public function testResult() {
		$response = $this->client->get('/wordpress/wp-json/extensions/v1/multisites/');
		$this->assertEquals(200, $response->getStatusCode());

		$body = $response->getBody();
		echo $body;
		$this->assertNotNull($body);

		$data = json_decode($body, true);
		$this->assertNotNull($data);
		$this->assertInternalType('array', $data);
		$this->assertNotEmpty($data);

		$site = $data[0];
		$this->assertInternalType('array', $site);
		$this->assertArrayHasKey('id', $site);
		$this->assertArrayHasKey('description', $site);
		$this->assertArrayHasKey('name', $site);
		$this->assertArrayHasKey('path', $site);
		$this->assertArrayHasKey('color', $site);
		$this->assertArrayHasKey('icon', $site);
		$this->assertArrayHasKey('cover_image', $site);
		$this->assertArrayHasKey('live', $site);
	}
}
