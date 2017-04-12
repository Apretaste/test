<?php

class Test extends Service
{
	/**
	 * Function executed when the service is called
	 *
	 * @param Request
	 * @return Response
	 */
	public function _main(Request $request)
	{
		// do not allow users without permission
		if( ! $this->hasPermissions($request->email)) return new Response();

		// get the list of domains
		$domains = $this->getDomains();

		// set the variables for the view
		$content = array(
			"total" => count($domains),
			"domains" => $domains);

		// send response
		$response = new Response();
		$response->setResponseSubject($this->utils->randomSentence());
		$response->setEmailLayout("email_empty.tpl");
		$response->createFromTemplate('domains.tpl', $content);
		return $response;
	}

	/**
	 * Subsevice buzones
	 *
	 * @author kuma
	 * @param Request $request
	 * @return Response
	 */
	public function _full($request)
	{
		// do not allow users without permission
		if( ! $this->hasPermissions($request->email)) return new Response();

		// get the list of domains
		$domains = $this->getDomains();

		// send the emails
		$sender = new Email();
		foreach ($domains as $domain)
		{
			// create the subject and body
			$dp = explode(".", $domain);
			$subject = $dp[0] . " " . $this->utils->randomSentence(1) . " " . $dp[1];
			$body = $this->utils->randomSentence();

			// send the email
			$sender->setDomain($domain);
			$sender->sendEmail($request->email, $subject, $body);
		}

		// do not sent any other emails
		return new Response();
	}

	/**
	 * Enforce user permissions
	 *
	 * @author salvipascual
	 * @param $email
	 * @return Boolean
	 */
	private function hasPermissions($email)
	{
		// check if the user/pass is ok
		$connection = new Connection();
		$res = $connection->query("SELECT email FROM manage_users WHERE email='$email'");
		return !empty($res);
	}

	/**
	 * Get the list of active domains as array
	 *
	 * @author salvipascual
	 */
	private function getDomains()
	{
		// get the domain with less usage
		$connection = new Connection();
		$result = $connection->query("
			SELECT domain
			FROM domain
			WHERE active = 1
			AND blacklist NOT LIKE '%nauta.cu%'");

		// clean to return just the list of domains
		$domains = array();
		foreach ($result as $item) $domains[] = $item->domain;
		return $domains;
	}
}
