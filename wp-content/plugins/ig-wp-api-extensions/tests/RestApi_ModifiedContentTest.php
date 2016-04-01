<?php

/**
 * Author: Martin
 * Created: 27.09.2015 10:03
 */
class RestApi_ModifiedContentTest extends PHPUnit_Framework_TestCase {
	public function test() {
		$route = 'http://localhost/wordpress/augsburg/de/wp-json/extensions/v0/
					modified_content/posts_and_pages';
		$r = new HttpRequest($route, HttpRequest::METH_GET);
		$r->addQueryData(array('since' => '2000-01-01T00:00:00Z'));
		$r->send();
		$this->assertEquals(200, $r->getResponseCode());
		$body = $r->getResponseBody();
	}
}
