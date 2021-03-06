<?php

namespace Request\Curl;

use Request\AbstractRequest;
use Request\Response;
use Request\Cookie;

class Request extends AbstractRequest {

	const USER_AGENT_STRING = "Bellows cURL";

	protected $opts = array();

	public function setOpt($curlOpt, $value) {
		$this->opts[$curlOpt] = $value;
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

		$handle = curl_init($url);

		$this->setOpt(CURLOPT_USERAGENT, self::USER_AGENT_STRING);
		$this->setOpt(CURLOPT_HEADER, true);
		$this->setOpt(CURLOPT_BINARYTRANSFER, true);
		$this->setOpt(CURLOPT_RETURNTRANSFER, true);

		if (! is_null($this->formData)) {
			if (is_null($this->formDataEncoding)) {
				$payload = http_build_query($this->formData);
			} else if ($this->formDataEncoding == 'json') {
				$payload = json_encode($this->formData);

				$this->httpHeaderIfNotSet('Accept', 'application/json');
				$this->httpHeaderIfNotSet('Content-type', 'application/json; charset=utf-8');
			}
			$this->ifNotSet(CURLOPT_CUSTOMREQUEST, "POST");
			$this->setOpt(CURLOPT_POSTFIELDS, $payload);

		}

		$cookies = array();
		foreach ($this->cookies as $cookie) {
			$cookies[] = $cookie->getValue();
		}
		if ($cookies) {
			$this->setOpt(CURLOPT_COOKIE, join(';',$cookies));
		}
		if ($this->timeout) {
			$this->setOpt(CURLOPT_TIMEOUT, $this->timeout);
		}
		$this->ifNotSet(CURLOPT_HTTPHEADER, array());
		foreach ($this->httpHeaders as $key => $value) {
			if (is_null($value)) {
				$this->opts[CURLOPT_HTTPHEADER][] = $key;
			} else {
				$this->opts[CURLOPT_HTTPHEADER][] = sprintf("%s: %s", $key, $value);
			}
		}

		if ($this->method != 'GET') {
			$this->setOpt(CURLOPT_CUSTOMREQUEST, $this->method);
		}

		foreach ($this->opts as $opt => $value) {
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
				list($key, $value) = explode(':', $headerLine);
				$headers[trim($key)] = trim($value);
			}
		}

		if ($type == 'application/json') {
			$body = json_decode($rawBody, true);
		} else {
			$body = $rawBody;
		}

		return new Response($rawBody, $body, $headers, $status, $type, $cookies);
	}

}
