<?php

class ExampleTest extends TestCase {

	/**
	 * A basic functional test example.
	 *
	 * @return void
	 */
	public function testLoginForm()
	{
		$crawler = $this->client->request('GET', '/login');

		$this->assertTrue($this->client->getResponse()->isOk());
	}

}