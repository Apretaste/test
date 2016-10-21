<?php

class Test extends Service {

	/**
	 * Function executed when the service is called
	 *
	 * @param Request
	 * @return Response
	 * */
	public function _main(Request $mainRequest){

		$connection = new Connection();
		
		// expecting subject = [mailbox] [service] (subservice) (subject)
		$q = trim($mainRequest->query);
		
		// get mailbox
		$p = strpos($q, ' ');
		if ($p !== false)
		{
			$mailbox = trim(substr($q, 0 , $p));
			
			if ( ! Email::isJumper($mailbox))
			{
				if (strtolower($mailbox) == 'full')
				{
					$mailbox = array();
					$jumpers = $this->utils->getJumpers();
					foreach($jumpers as $jp)
						$mailbox[] = $jp->email;
				}
				else 
					return new Response();
			}
			else 
			{
				$mailbox = array($mailbox);
			}
			
			$q = trim(substr($q, $p)); // cut!
				
			// get service name
			$p = strpos($q, ' ');
			if ($p !== false)
			{
				$subjectPieces = explode(" ", $q);
				$serviceName = strtolower($subjectPieces[0]);
				unset($subjectPieces[0]);
								
				// $serviceName = strtolower(trim(substr($q, 0 , $p)));
				$q = trim(substr($q, $p)); // cut!
				
				// include the service code
				$di = \Phalcon\DI\FactoryDefault::getDefault();
				$wwwroot = $di->get('path')['root'];
				include "$wwwroot/services/$serviceName/service.php";
					
				// check if a subservice is been invoked
				$subServiceName = "";
				if(isset($subjectPieces[1]) && ! preg_match('/\?|\(|\)|\\\|\/|\.|\$|\^|\{|\}|\||\!/', $subjectPieces[1]))
				{
					$serviceClassMethods = get_class_methods($serviceName);
					if(preg_grep("/^_{$subjectPieces[1]}$/i", $serviceClassMethods))
					{
						$subServiceName = strtolower($subjectPieces[1]);
						unset($subjectPieces[1]);
					}
				}
					
				// get the service query
				$query = implode(" ", $subjectPieces);
					
				// create a new Request object
				$request = new Request();
				$request->email = $mainRequest->email;
				$request->name = $mainRequest->name;
				$request->subject = $serviceName.' '.$subServiceName.' '.$query;
				$request->body = $mainRequest->body;
				$request->attachments = $mainRequest->attachments;
				$request->service = $serviceName;
				$request->subservice = trim($subServiceName);
				$request->query = trim($query);
					
				// get the path to the service
				$servicePath = $this->utils->getPathToService($serviceName);
				
				// get details of the service
				if($di->get('environment') == "sandbox")
				{
					// get details of the service from the XML file
					$xml = simplexml_load_file("$servicePath/config.xml");
					$serviceCreatorEmail = trim((String)$xml->creatorEmail);
					$serviceDescription = trim((String)$xml->serviceDescription);
					$serviceCategory = trim((String)$xml->serviceCategory);
					$serviceUsageText = trim((String)$xml->serviceUsage);
					$showAds = isset($xml->showAds) && $xml->showAds==0 ? 0 : 1;
					$serviceInsertionDate = date("Y/m/d H:m:s");
				}
				else
				{
					// get details of the service from the database
					$sql = "SELECT * FROM service WHERE name = '$serviceName'";
					$result = $connection->deepQuery($sql);
				
					$serviceCreatorEmail = $result[0]->creator_email;
					$serviceDescription = $result[0]->description;
					$serviceCategory = $result[0]->category;
					$serviceUsageText = $result[0]->usage_text;
					$serviceInsertionDate = $result[0]->insertion_date;
					$showAds = $result[0]->ads == 1;
				}
				
				// create a new service Object of the user type
				$userService = new $serviceName();
				$userService->serviceName = $serviceName;
				$userService->serviceDescription = $serviceDescription;
				$userService->creatorEmail = $serviceCreatorEmail;
				$userService->serviceCategory = $serviceCategory;
				$userService->serviceUsage = $serviceUsageText;
				$userService->insertionDate = $serviceInsertionDate;
				$userService->pathToService = $servicePath;
				$userService->showAds = $showAds;
				$userService->utils = $this->utils;
				
				// run the service and get a response
				if(empty($subServiceName))
				{
					$response = $userService->_main($request);
				}
				else
				{
					$subserviceFunction = "_$subServiceName";
					$response = $userService->$subserviceFunction($request);
				}
				
				// a service can return an array of Response or only one.
				// we always treat the response as an array
				$responses = is_array($response) ? $response : array($response);
				
				// clean the empty fields in the response
				foreach($responses as $rs)
				{
					$rs->email = empty($rs->email) ? $mainRequest->email : $rs->email;
					$rs->subject = empty($rs->subject) ? "Respuesta del servicio $serviceName" : $rs->subject;
					$rs->content['num_notifications'] = $this->utils->getNumberOfNotifications($rs->email);
				}
				
				// create a new render
				$render = new Render();
				
				// create and configure to send email
				$emailSender = new Email();
				$emailSender->setRespondEmailID('');
				$emailSender->setEmailGroup('');
				$emailTo = $mainRequest->email;
				$subject = $rs->subject;
				$images = $rs->images;
				$attachments = $rs->attachments;
				$body = $render->renderHTML($userService, $rs);
				
				// remove dangerous characters that may break the SQL code
				$subject = trim(preg_replace('/\'|`/', "", $subject));
				
				// send the response email
				foreach ($mailbox as $mb)
					$emailSender->sendEmail($emailTo, $subject, $body, $images, $attachments, $mb, true);
			}
		}
		
		return new Response();
	}
	
	/**
	 * Subsevice buzones
	 * 
	 * @author kuma
	 * @param Request $request
	 * @return Response
	 */
	public function _buzones($request)
	{
		$jumpers = $this->utils->getJumpers();
		
		$services = array(
			'WEB cuba',
			'PIZARRA',
			'AYDUA',
			'CHISTE',
			'SERVICIOS',
			'SINONIMO casa',
			'TRADUCIR casa',
			'WIKIPEDIA cuba',
			'GOOGLE cuba',
			'CLIMA',
			'CLIMA SATELITE',
			'CLIMA RADAR',
			'MAPA havana',
			'DOCTOR amigdalitis',
			'MARTI',
			'TECNOLOGIA',
			'PUBLICIDAD',
			'TIENDA laptop',
			'AJEDREZ',
			'BILLBOARD',
			'LETRA one',
			'PARTITURA dreamer',
			'RIFA',
			'STARWARS',
			'SUDOKU',
			'CUPIDO',
			'ENCUESTA',
			'PERFIL',
			'PIZARRA',
			'BUZONES',
			'NOTIFICACIONES',
			'TERMINOS'
		);
		
		$jps = array();
		foreach ($jumpers as $jumper)
			$jps[] = array(
				'mailbox' => $jumper->email,
				'service' => $services[mt_rand(0,count($services)-1)]
			);
		
		$response = new Response();
		$response->setResponseSubject('buzones');
		$response->createFromTemplate('basic.tpl', array(
			"buzones" => $jps,
			"service" => $services[mt_rand(0,count($services)-1)]
		));
		return $response;
	}
}