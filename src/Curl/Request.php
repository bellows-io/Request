<?php

namespace Request\Curl;

use Request\AbstractRequest;
use Request\Response;
use Request\Cookie;

class Request extends AbstractRequest {

	const USER_AGENT_STRING = "BitMoth cURL";

	protected $opts = array();
	protected $cookieVals = array();

	public function setOpt($curlOpt, $value) {
		$this->opts[$curlOpt] = $value;
	}

	public function cookie($key, $value) {
		$this->cookieVals[$key] = $value;
	}

	protected function httpHeaderIfNotSet($key, $value) {
		if (! isset($this->httpHeaders[$key])) {
			$this->httpHeaders[$key] = $value;
		}
	}

	protected function ifNotSet($curlOpt, $value) {
		if (! isset($this->opts[$curlOpt])) {
			$this->opts[$curlOpt] = $value;
		}
	}

	public function exec() {
		$url = $this->buildUrl();

		$opts = $this->opts;

		$handle = curl_init($url);

		$opts[ CURLOPT_USERAGENT ] = self::USER_AGENT_STRING;
		$opts[ CURLOPT_HEADER ] = true;
		$opts[ CURLOPT_BINARYTRANSFER ] = true;
		$opts[ CURLOPT_RETURNTRANSFER ] = true;

		if (! is_null($this->formData)) {
			if (is_null($this->formDataEncoding)) {
				$payload = http_build_query($this->formData);
			} else if ($this->formDataEncoding == 'json') {
				$payload = json_encode($this->formData);

				$this->httpHeaderIfNotSet('Accept', 'application/json');
				$this->httpHeaderIfNotSet('Content-type', 'application/json; charset=utf-8');
			}
			if ( ! isset( $opts[ CURLOPT_CUSTOMREQUEST ])) {
				$opts[ CURLOPT_CUSTOMREQUEST ] = "POST";
			}
			$opts[ CURLOPT_POSTFIELDS ] = $payload;
		}

		$cookies = array();
		foreach ($this->cookieVals as $key => $value) {
			$string = sprintf("%s=%s", $key, $value);
			$cookies[] = $string;
		}
		if ($cookies) {
			$opts[ CURLOPT_COOKIE ] = join(';',$cookies);
		}
		if ($this->timeout) {
			$opts[ CURLOPT_TIMEOUT ] = $this->timeout;
		}
		if ( ! isset( $opts[ CURLOPT_HTTPHEADER ])) {
			$opts[ CURLOPT_HTTPHEADER ] = [];
		}
		foreach ($this->httpHeaders as $key => $value) {
			if (is_null($value)) {
				$opts[CURLOPT_HTTPHEADER][] = $key;
			} else {
				$opts[CURLOPT_HTTPHEADER][] = sprintf("%s: %s", $key, $value);
			}
		}

		if ($this->method != 'GET') {
			$opts[ CURLOPT_CUSTOMREQUEST ] = $this->method;
		}

		foreach ($opts as $opt => $value) {
			curl_setopt($handle, $opt, $value);
		}

		$response = curl_exec($handle);

		if ($response === false) {
			throw new \Exception(sprintf("cURL Error %d: %s", curl_errno($handle), curl_error($handle)));
		}

		$headerLength = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
		curl_close($handle);

		$header = trim(substr($response, 0, $headerLength));
		$headerLines = explode("\n", $header);

		$rawBody = substr($response, $headerLength);

		$cookies = array();
		$status = null;
		$type = '';
		$headers = [];
		foreach ($headerLines as $headerLine) {
			if (preg_match('/HTTP\/1\.1 ([0-9]{3})/', $headerLine, $matches)) {
				$status = $matches[1];
			} else {
				if (preg_match('/^Set-Cookie: (.+)$/', $headerLine, $matches)) {
					$cookies[] = Cookie::parse($headerLine);
				} else if (preg_match('/Content\-Type: (.+);?/', $headerLine, $matches)) {
					$type = $matches[1];
				}
				$parts = explode(':', $headerLine);
				$key = array_shift( $parts );
				$headers[trim($key)] = trim(implode(':', $parts));
			}
		}

		if ( $status && $status[ 0 ] == '3' && $this->followRedirects && !empty($headers['Location'])) {
			$this->setUrl( $headers['Location'] );
			return $this->exec();
		}

		if ($type == 'application/json') {
			$body = json_decode($rawBody, true);
		} else {
			$body = $rawBody;
		}

		$this->opts = $opts;
		return new Response($rawBody, $body, $headers, $status, $type, $cookies);
	}

}
