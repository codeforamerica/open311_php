<?php

class open311Api extends APIBaseClass{
	// worldBankApi does not use /need API key.
	
	// will need to define what API URL or 'city' in construct method
	// https://open311.sfgov.org/dev/V2/
	public static $api_url = 'https://open311.sfgov.org/dev/V2/';
	
	public static $api_key = '';
	
	// if this is not set you must enter in jurisdiction ID's for all methods
	public static $jurisdiction_id = 'sfgov.org';
	public function __construct($url=NULL)
	{
		parent::new_request(($url?$url:self::$api_url));
	}
	
	public function get_service_list($jurisdiction_id=NULL,$format='xml'){
	/*	GET Service List
		    Purpose: provide a list of acceptable 311 service request types and their associated service codes. These request types can be unique to the city/jurisdiction.
		    URL: https://[API endpoint]/services.[format]
		    Sample URL: https://open311.sfgov.org/dev/v2/services.xml?jurisdiction_id=sfgov.org
		    Formats: XML (JSON available if denoted by Service Discovery)
		    HTTP Method: GET
		    Requires API Key: No 
		Required Arguments
		    jurisdiction_id 
		Response
		    services	root element 
		    service			Parent: services	 container for service_code, service_name, description, metadata, type, keywords, group 
		    service_code	Parent: service		The unique identifier for the service request type 
		    service_name	Parent: service		The human readable name of the service request type 
		    description		Parent: service		A brief description of the service request type. 
		    metadata		Parent: service		Possible values: true, false
		            				true: This service request type requires additional metadata so the client will need to make a call to the Service Definition method
		            				false: No additional information is required and a call to the Service Definition method is not needed. 
		    type			Parent: service		Possible values: realtime, batch, blackbox
		            				realtime: The service request ID will be returned immediately after the service request is submitted
		           					batch: A token will be returned immediately after the service request is submitted. This token can then be later used to return the service request ID.
		            				blackbox: No service request ID will be returned after the service request is submitted 
		   
		    keywords		Parent: service		A comma separated list of tags or keywords to help users identify the request type. This can provide synonyms of the service_name and group. 
		    group			Parent: service		A category to group this service type within. This provides a way to group several service request types under one category such as "sanitation" 

	*/
	
		return $this->_request($path."/services.$format?jurisdiction_id=". self::default_jurisdiction($jurisdiction_id), 'get' ,$data) ;
	}
	
	private function default_jurisdiction($id){
	// helper to automatically set jurisdiction if it is not provided in method calls.
			return ($id == NULL && self::$jurisdiction?self::$jurisdiction:($id!=NULL?$id:false));
	}
	
	public function get_service_definition($service_code,$jurisdiction_id=null,$format='sml'){
	/*
	
	Conditional: Yes - This call is only necessary if the Service selected has metadata set as true from the GET Services response
    Purpose: define attributes associated with a service code. These attributes can be unique to the city/jurisdiction.
    URL: https://[API endpoint]/services/[service_code].[format]
    Sample URL: https://open311.sfgov.org/dev/v2/services/033.xml?jurisdiction_id=sfgov.org
    Formats: XML (JSON available if denoted by Service Discovery)
    HTTP Method: GET
    Requires API Key: No 
	
	*/
		return $this->_request($path."/services/$service_code.$format?jurisdiction_id=".self::default_jurisdiction($jurisdiction_id), 'get' ,$data) ;
	}
	
	public function post_service_request($jurisdiction_id,$service_code,$options,$format='xml'){
	/*POST Service Request
	
	 Purpose: Create service requests
    URL: https://[API endpoint]/requests.[format]
    Sample URL: https://open311.sfgov.org/dev/v2/requests.xml
    Format sent: Content-Type: application/x-www-form-urlencoded
    Formats returned: XML (JSON available if denoted by Service Discovery)
    HTTP Method: POST
    Requires API Key: Yes 

	Required Arguments
	
	    jurisdiction_id
	    service_code (obtained from GET Service List method)
	    location: either lat & long or address_string or address_id must be submitted 
	
	Optional Arguments
	
	    lat				  latitude using the (WGS84) projection. 
	    long			  longitude using the (WGS84) projection. 
	    address_string	  Human entered address or description of location. This should be written from most specific to most general geographic unit, eg address number or cross streets, street name, neighborhood/district, city/town/village, county, postal code. This is required if no lat/lon is provided. 
	    address_id		  The internal address ID used by a jurisdictions master address repository or other addressing system. 
	    email			  The email address of the person submitting the request 
	    device_id		  The unique device ID of the device submitting the request. This is usually only used for mobile devices. For example, Android devices use TelephonyManager.getDeviceId() and iPhone's use [UIDevice currentDevice].uniqueIdentifier 
	    account_id		  The unique ID for the user account of the person submitting the request 
	    first_name		  The given name of the person submitting the request 
	    last_name		  The family name of the person submitting the request 
	    phone			  The phone number of the person submitting the request 
	    description		  A full description of the request or report being submitted. This may contain line breaks, but not html or code. Otherwise, this is free form text limited to 4,000 characters. 
	    media_url		  A URL to media associated with the request, eg an image. A convention for parsing media from this URL has yet to be established, so currently it will be done on a case by case basis. For example, if a jurisdiction accepts photos submitted via Twitpic.com, then clients can parse the page at the Twitpic URL for the image given the conventions of Twitpic.com. This could also be a URL to a media RSS feed where the clients can parse for media in a more structured way. 
	    attribute		  An array of key/value responses based on Service Definitions. This takes the form of attribute[code]=value where multiple code/value pairs can be specified as well as multiple values for the same code in the case of a multivaluelist datatype (attribute[code1][]=value1&attribute[code1][]=value2&attribute[code1][]=value3) - see example 

	*/
	// this method requires specific options, Processing exists to only process the required options, and to ignore others once the required option is found
	// this option has to do with the address, which can take several parameters - address_String, address_id or both lat/long.	
		$required_opt = array('lat','long','address_string','address_id');
		$data['api_key'] = $this->$api_key;
		$data['jurisdiction_id'] = self::default_jurisdiction($jurisdiction_id);
		
		foreach($options as $key=>$value){
		// fancy processing that allows me to stop looking for required options when they are found (including some recursive lat/long trickery)
			if( array_search($key,$required_opt) && !$found && !$found_2){
			
				if($key == 'address_string' || $key == 'address_id'){
					$found = true;
					$found_2 = true;
					}
				
				if($key == 'lat' || $key == 'long')
					$found_2 = ($key == 'lat'?'long':'lat');
				
				if($found_2 && $key == $found_2)
					$found = true;
				
				$data[$key] = $value;
				
			}elseif(!array_search($key,$required_opt))
			// we don't want to store extra option parameters we dont need (incase a developer gives us too many parameters)
				$data[$key] = $value;
		}

	return $this->_request($path."/requests.$format", 'post' ,$data) ;

	}
	
	public function get_service_requests($jurisdiction_id,$options=NULL,$format='xml'){
	/*	GET Service Requests
	
	    Purpose: query the current status of multiple requests
	    URL: https://[API endpoint]/requests.[format]
	    Sample URL: https://open311.sfgov.org/dev/v2/requests.xml?start_date=2010-05-24T00:00:00Z&end_date=2010-06-24T00:00:00Z&status=open&jurisdiction_id=sfgov.org
	    Formats: XML (JSON available if denoted by Service Discovery)
	    HTTP Method: GET
	    Requires API Key: No 
		Required Arguments 	    jurisdiction_id 
		Optional Arguments
		    service_request_id		To call multiple Service Requests at once, multiple service_request_id can be declared; comma delimited. This overrides all other arguments. 
		    service_code			Specify the service type by calling the unique ID of the service_code. This defaults to all service codes when not declared; can be declared multiple times, comma delimited 
		    start_date				Earliest datetime to include in search. When provided with end_date, allows one to search for requests which have a requested_datetime that matches a given range, but may not span more than 90 days. When not specified, the range defaults to most recent 90 days. Must use w3 format, eg 2010-01-01T00:00:00Z. 
		    end_date				Latest datetime to include in search. When provided with start_date, allows one to search for requests which have a requested_datetime that matches a given range, but may not span more than 90 days. When not specified, the range defaults to most recent 90 days. Must use w3 format, eg 2010-01-01T00:00:00Z. 
		    status					Options: open, closed		Allows one to search for requests which have a specific status. This defaults to all statuses; can be declared multiple times, comma delimited; 
	
	
	*/
	$data ['jurisdiction_id'] = self::default_jurisdiction($jurisdiction_id);
	
	if($options != NULL){
		$service_opt = array('service_request_id','service_code','start_date','end_date','status');
		foreach($options as $key=>$value) {
			if(array_search($key,$service_opt)){
				if($key == 'status' && ($value != 'open' || $value != 'closed'))
				 	// do nothing
				 	;
				 else
				 	$data_2[$key] = $value;
			}		 
		}
		
		if($data_2){
			$data = array_merge($data,$data2);
			unset($data2);
		}
		unset($service_opt);
	}
	
	return $this->_request($path."/requests.$format", 'get' ,$data) ;
	}
	
	public function get_service_request($service_request_id,$jurisdiction_id,$format=NULL){
	/* GET Service Request
	
	    Purpose: query the current status of an individual request
	    URL: https://[API endpoint]/requests/[service_request_id].[format]
	    Sample URL: https://open311.sfgov.org/dev/v2/requests/123456.xml?jurisdiction_id=sfgov.org
	    Formats: XML (JSON available if denoted by Service Discovery)
	    HTTP Method: GET
	    Requires API Key: No 
			Required Arguments
				service_request_id
			    jurisdiction_id 
			Response
			    service_requests	  root element 
			    request				Parent: service_requests		   container for variable, code, datatype, required, datatype_description, order, description, values 
			    service_request_id	Parent: request		  The unique ID for the service request returned 
			    status				Parent: request	 	Options: open, closed	   The current status of the service request. Open means that it has been reported. Closed means that it has been resolved. 
			    status_notes		Parent: request		  Explanation of why status was changed to current state or more details on current status than conveyed with status alone. (May not be returned) 
			    service_name		Parent: request		  The human readable name of the service request type 
			    service_code		Parent: request		  The unique identifier for the service request type 
			    description			Parent: request		  A full description of the request or report submitted. This may contain line breaks, but not html or code. Otherwise, this is free form text limited to 4,000 characters. 
			    agency_responsible	Parent: request		  The agency responsible for fulfilling or otherwise addressing the service request. (May not be returned) 
			    service_notice		Parent: request		  Information about the action expected to fulfill the request or otherwise address the information reported. (May not be returned) 
			    requested_datetime	Parent: request		  The date and time when the service request was made. Returned in w3 format, eg 2010-01-01T00:00:00Z 
			    updated_datetime	Parent: request		  The date and time when the service request was last modified. For requests with status=closed, this will be the date the request was closed. Returned in w3 format, eg 2010-01-01T00:00:00Z 
			    expected_datetime	Parent: request		  The date and time when the service request can be expected to be fulfilled. This may be based on a service-specific service level agreement. (May not be returned) 
			    address				Parent: request		  Human readable address or description of location. This should be provided from most specific to most general geographic unit, eg address number or cross streets, street name, neighborhood/district, city/town/village, county, postal code. 
			    address_id			Parent: request		  The internal address ID used by a jurisdictions master address repository or other addressing system. 
			    zipcode				Parent: request 	  The postal code for the location of the service request. 
			    lat 				Parent: request 	  latitude using the (WGS84) projection. 
			    long 				Parent: request 	  longitude using the (WGS84) projection. 
			    media_url 			Parent: request 	  A URL to media associated with the request, eg an image. A convention for parsing media from this URL has yet to be established, so currently it will be done on a case by case basis. For example, if a jurisdiction accepts photos submitted via Twitpic.com, then clients can parse the page at the Twitpic URL for the image given the conventions of Twitpic.com. This could also be a URL to a media RSS feed where the clients can parse for media in a more structured way. 

	
	*/
		return $this->_request($path."/requests/$service_request_id.$format?jurisdiction_id=".self::default_jurisdiction($jurisdiction_id), 'get') ;
	}
	}
