<?php

require_once dirname( __FILE__ ) . '/../../AmberInterfaces.php';
require_once dirname( __FILE__ ) . '/../../AmberNetworkUtils.php';
require_once dirname( __FILE__ ) . '/../../AmberChecker.php';

class InternetArchiveFetcher implements iAmberFetcher {

  function __construct(iAmberStorage $storage, array $options) {
    $this->archiveUrl = "http://web.archive.org"; //https://archive.org/wayback/available?url=
    $this->archive_available_Url = "https://archive.org/wayback/available"; 
  }

  /**
   * Fetch the URL and associated assets and pass it on to the designated Storage service
   * @param $url
   * @return
   */
	public function fetch($url) {
	error_log(join(":", array(__FILE__, __METHOD__, "InternetArchiveFetcher Triggered")));
    if (!$url) {
      throw new RuntimeException("Empty URL");
    }

    $api_endpoint = join("",array(
      $this->archive_available_Url,
      "?url=",
      $url));
    $cURL_options_arr = array(CURLOPT_SSL_VERIFYHOST => FALSE,CURLOPT_SSL_VERIFYPEER=>FALSE); //InternetArchive uses self-signed SSL certificate.  
    $ia_result = AmberNetworkUtils::open_single_url($api_endpoint, $cURL_options_arr, TRUE);
	
   // Make sure that we got a valid response from the Archive 
	error_log(join(":", array(__FILE__, __METHOD__, json_encode($ia_result), $url)));
	
    if ($ia_result === FALSE) {      
		//error_log(join(":", array(__FILE__, __METHOD__, "IA Result is False")));
      throw new RuntimeException(join(":",array("Error submitting to Internet Archive")));
    }
	$ia_result_body = json_decode($ia_result["body"],TRUE);
	$ia_result_headers = $ia_result["headers"];
	$ia_result_info = $ia_result["info"];
	//error_log(join(":", array(__FILE__, __METHOD__, json_encode($ia_result_body), $url)));
    if (isset($ia_result_info['http_code']) && ($ia_result_info['http_code'] == 403)) {
		//error_log(join(":", array(__FILE__, __METHOD__, "IA 403")));
      throw new RuntimeException(join(":",array("Permission denied when submitting to Internet Archive (may be blocked by robots.txt)")));
    } 
	
    if (!isset($ia_result_body['archived_snapshots'])) {
		//error_log(join(":", array(__FILE__, __METHOD__, "No archived_snapshots")));
      throw new RuntimeException("Internet Archive response did not include archive location");  
    }
	if (!isset($ia_result_body['archived_snapshots']["closest"])) {
		//error_log(join(":", array(__FILE__, __METHOD__, "No archived_snapshots")));
      $link_is_live = (new AmberChecker)->up($url);
	  error_log(join(":", array(__FILE__, __METHOD__, "link_is_live",$link_is_live ? 'true' : 'false')));
	  if ($link_is_live==TRUE)
		{
		//error_log(join(":", array(__FILE__, __METHOD__, "Site is up.",$url)));
		InternetArchiveFetcher::save_to_internet_archive($url);
		}
	throw new RuntimeException("Internet Archive has not saved this page yet");  
    }
    $location = $ia_result_body["archived_snapshots"]["closest"]["url"];
	//error_log(join(":", array(__FILE__, __METHOD__, "Found Archive Link",$location)));
    $content_type = ""; //isset($ia_result['headers']['X-Archive-Orig-Content-Type']) ? $ia_result['headers']['X-Archive-Orig-Content-Type'] : "";
    $size = 0; //isset($ia_result['headers']['X-Archive-Orig-Content-Length']) ? $ia_result['headers']['X-Archive-Orig-Content-Length'] : 0;
    $result = array (
        'id' => md5($url),
        'url' => $url,
        'type' => $content_type,
        'date' => time(),
        'location' => $location,
        'size' => $size,
        'provider' => 2, //Internet Archive 
        'provider_id' => $location,
      );
	
    return $result;
	}
	//public function fetch($url) {
	public function save_to_internet_archive($url) { //NOT WORKING!!! old fetch function makes new saves in internet archive.
    if (!$url) {
      throw new RuntimeException("Empty URL");
    }
	error_log(join(":", array(__FILE__, __METHOD__, "Save to InternetArchive Triggered")));
    $api_endpoint = join("",array(
      $this->archiveUrl,
      "/save/",
      $url));
    InternetArchiveFetcher::enqueue_check_links(array($url)); //needed because internet archive won't return right away and availibility API does not update instantly.
    //$ia_result = AmberNetworkUtils::open_single_url($api_endpoint, array(), FALSE);
	$ia_result = AmberNetworkUtils::post_single_url($api_endpoint, array("url" => $url,"capture_all" => "on"),array(),FALSE);
	//error_log(join(":", array(__FILE__, __METHOD__, "Attempted to save to internet archive",$url)));
    /* Make sure that we got a valid response from the Archive */

    /*if ($ia_result === FALSE) {      
      throw new RuntimeException(join(":",array("Error submitting to Internet Archive")));
    }
    if (isset($ia_result['info']['http_code']) && ($ia_result['info']['http_code'] == 403)) {
      throw new RuntimeException(join(":",array("Permission denied when submitting to Internet Archive (may be blocked by robots.txt)")));
    } 
    if (!isset($ia_result['headers']['location'])) {
      throw new RuntimeException("Internet Archive response did not include archive location");  
    }

    $location = $ia_result['headers']['location'];
    $content_type = isset($ia_result['headers']['content-type']) ? $ia_result['headers']['content-type'] : "";
    $size = isset($ia_result['headers']['content-length']) ? $ia_result['headers']['content-length'] : 0;
    $result = array (
        'id' => md5($url),
        'url' => $url,
        'type' => $content_type,
        'date' => time(),
        'location' => $this->archiveUrl . $location,
        'size' => $size,
        'provider' => 2, // Internet Archive 
        'provider_id' => $location,
      );
    return $result;*/
	}
	
	/**
	 * Add links that need to be checked to our queue to be checked at some point in the future
	 * Do not insert or update if the link already exists in the queue
	 */
	private static function enqueue_check_links($links)
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		foreach ($links as $link) {
			$query = $wpdb->prepare(
				"INSERT IGNORE INTO ${prefix}amber_queue (id, url, created) VALUES(%s, %s, %d)",
				array(md5($link), $link, time()));
			$wpdb->query($query);
		}
	}
	
}