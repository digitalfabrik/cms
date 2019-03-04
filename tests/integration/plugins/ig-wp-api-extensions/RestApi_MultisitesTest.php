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
	}

	public function testResult() {
		$ch = curl_init("http://localhost/wordpress/wp-json/extensions/v1/multisites/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$body = curl_exec($ch);
		$this->assertEquals(200, curl_getinfo($ch, CURLINFO_HTTP_CODE));
		curl_close($ch);
		echo "SNAFU\n";
		var_dump($body);
		$this->assertNotNull($body);
		echo "FOO\n";
		var_dump($body);
		echo "BAZ\n";
		$data = json_decode($body, true);
		var_dump($data);
		echo "BAR\n";
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
