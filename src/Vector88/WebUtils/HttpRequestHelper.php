<?php

namespace Vector88\WebUtils;


class HttpResponseHelper {
	
	public $success;
	public $data;
	public $code;
	
	public $headerSize;
	public $headers;
	public $body;
	
	public $errno;
	public $errstr;
	
	public function __construct() {
		$this->headers = array();
	}
	
}

class HttpRequestHelper {
	
	private $_uri;
	
	private $_authUsername;
	private $_authPassword;
	
	private $_method;
	private $_headers;
	private $_body;
	
	public function __construct( $uri = null ) {
		$this->_uri = $uri;
		$this->_headers = array();
	}
	
	public function uri( $value ) {
		$this->_uri = $value;
		return $this;
	}
	
	public function authUsername( $value ) {
		$this->_authUsername = $value;
		return $this;
	}
	
	public function authPassword( $value ) {
		$this->_authPassword = $value;
		return $this;
	}
	
	public function method( $value = "GET" ) {
		$this->_method = $value;
		return $this;
	}
	
	public function header( $key, $value ) {
		$this->_headers[ $key ] = $value;
		return $this;
	}
	
	public function body( $value ) {
		$this->_body = $value;
		return $this;
	}
	
	public function execute() {
		$request = $this->_buildRequest();
		$response = $this->_processRequest( $request );
		return $response;
	}
	
	private function _buildHeaders() {
		$allHeaders = array();
		
		foreach( $this->_headers as $header => $value ) {
			$allHeaders[ $header ] = $value;
		}
		
		$authHeader = $this->_getAuthHeader();
		if( null !== $authHeader ) {
			$allHeaders[ "Authorization" ] = $authHeader;
		}
		
		return $allHeaders;
	}
	
	private function _buildRequest() {
		$request = "";
		
		$allHeaders = $this->_buildHeaders();
		$allHeaders[ "Content-Length" ] = strlen( $this->_body );
		
		foreach( $allHeaders as $header => $value ) {
			$request .= "{$header}: {$value}\r\n";
		}
		
		$request .= "\r\n";
		$request .= $this->_body;
		
		return $request;
	}
	
	public function toString() {
		return $this->_buildRequest();
	}
	
	private function _getAuthHeader() {
		if( null === $this->_authUsername ) {
			return null;
		}
		
		$header = "{$this->_authUsername}:";
		
		if( null !== $this->_authPassword ) {
			$header .= $this->_authPassword;
		}
		
		$header = "Basic " . base64_encode( $header );
		
		return $header;
	}
	
	private function _processRequest( $request ) {
		
		$response = new HttpResponseHelper();
		
		$headers = array();
		foreach( $this->_buildHeaders() as $key => $value ) {
			$headers[] = "${key}: ${value}";
		}
		
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $this->_uri );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		
		if( null !== $this->_method ) {
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $this->_method );
		}
		
		if( null !== $this->_body ) {
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $this->_body );
		}
		
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HEADER, true );
		
		$response->data = curl_exec( $curl );
		$response->success = $response->data !== false;
		
		if( $response->success ) {
			$response->code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			
			$response->headerSize = curl_getinfo( $curl, CURLINFO_HEADER_SIZE );
			
			$headers = substr( $response->data, 0, $response->headerSize );
			
			$httpResponseLineDone = false;
			
			foreach( explode( "\r\n", $headers ) as $line ) {
				if( !$httpResponseLineDone ) {
					$httpResponseLineDone = true;
					continue;
				}
				
				$parts = explode( ":", $line );
				if( count( $parts ) < 2 ) {
					continue;
				}
				
				$key = $parts[ 0 ];
				$value = $parts[ 1 ];
				$response->headers[ $key ] = trim( $value );
			}
			
			$response->body = substr( $response->data, $response->headerSize );
			
		} else {
			$response->errno = curl_errno( $curl );
			$response->errstr = curl_error( $curl );
		}
		
		curl_close( $curl );
		
		return $response;
	}
	
}