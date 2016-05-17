<?php

class DataTest extends TestCase {

	/**
	 * A basic functional test example.
	 *
	 * @return void
	 */
	public function testExternalJobsCh()
	{
		$testSerial = Client::first()->serial;

        $externalRoute = '/data/'.$testSerial.'/jobs/external/jobsch';

		$crawler = $this->client->request('GET', $externalRoute);

		$this->assertTrue($this->client->getResponse()->isOk());

        $this->client->getResponse()->getContent();
        $crawler->filter('JOBS > INSERATE');

	}

}
