<?php

class Test extends Service
{
	/**
	 * Email the tester from each domain
	 *
	 * @author salvipascual
	 * @param Request $request
	 * @return Response
	 */
	public function _main(Request $request)
	{
		// do not allow users without permission
		if( ! $this->hasPermissions($request->email)) return new Response();

		// get the list of emails
		$connection = new Connection();
		$results = $connection->query("SELECT email FROM nodes_output WHERE active=1");

		// send the emails
		foreach ($results as $r) {
			$email = new Email();
			$email->from = $r->email;;
			$email->to = $request->email;
			$email->subject = $this->utils->randomSentence(1) . " " . explode("@", $r->email)[0];
			$email->body = $this->utils->randomSentence();
			$email->sendEmailViaNode();
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
		// do not allow users without permission
		if( ! $this->hasPermissions($request->email)) return new Response();

		// get the list of domains from the body
		$regex = "/(?:[A-Za-z0-9!#$%&'*+=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&'*+=?^_`{|}~-]+)*|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@(?:(?:[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[A-Za-z0-9-]*[A-Za-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/";
		preg_match_all($regex, $request->body, $emails);
		$emails = $emails[0];

		// update the last tested date for the domains
		$activeEmails = count($emails);
		$emailsCSV = implode(",", $emails);
		$sql = "INSERT INTO test (tester,count,domains) VALUES ('{$request->email}','$activeEmails','$emailsCSV');";
		foreach ($emails as $e) $sql .= "UPDATE nodes_output SET last_test=CURRENT_TIMESTAMP WHERE email = '$e';";

		// save into the database
		$connection = new Connection();
		$connection->query($sql);

		// do not send any confirmation email
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
}
