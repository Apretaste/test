<?php

class Test extends Service
{
	/**
	 * Get the list of "working" domains
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
	 * Email the tester from each domain
	 *
	 * @author salvipascual
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
	 * Let the tester share with us what domains are working
	 *
	 * @author salvipascual
	 * @param Request $request
	 * @return Response
	 */
	public function _feed($request)
	{
		// get the list of domains from the body
		$regex = '/[-A-Za-z0-9]*\.{1}[a-z]{2,4}/';
		preg_match_all($regex, $request->body, $domains);
		$domains = $domains[0];

		// save into table test
		$activeDomains = count($domains);
		$domainsCSV = implode(",", $domains);
		$sql = "INSERT INTO test (tester,count,domains) VALUES ('{$request->email}','$activeDomains','$domainsCSV');";

		// update the last tested date for the domains
		foreach ($domains as $domain) {
			$sql .= "UPDATE domain SET last_tested=CURRENT_TIMESTAMP WHERE domain = '$domain';";
		};

		// save all data into the database
		$connection = new Connection();
		$connection->query($sql);
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
