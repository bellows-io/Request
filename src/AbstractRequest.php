<?php

namespace Request;

abstract class AbstractRequest {

	protected $url;
	protected $query            = null;
	protected $formData         = null;
	protected $formDataEncoding = null;
	protected $timeout          = null;
	protected $cookies          = array();
	protected $method           = 'GET';
	protected $httpHeaders      = array();

	public function setUrl($url) {
		$this->url = $url;
	}

	public function setQuery($query) {
		$this->query = $query;
	}

	public function setFormData($formData, $encoding = null) {
		$this->formData = $formData;
		$this->formDataEncoding = $encoding;
	}

	public function addCookie(Cookie $cookie) {
		$this->cookies[] = $cookie;
	}

	public function addCookies(array $cookies) {
		foreach ($cookies as $cookie) {
			$this->addCookie($cookie);
		}
	}

	public function setTimeout($seconds) {
		$this->timeout = $seconds;
	}

	public function httpHeader($key, $value = null) {
		$this->httpHeaders[$key] = $value;
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public abstract function exec();

	public static function get($url, array $queryData = array()) {
		$request = new static();

		$request->setMethod('GET');
		$request->setUrl($url);
		if ($queryData) {
			$request->setQuery($queryData);
		}

		return $request->exec();
	}

	public static function post($url, $formData = array(), $formEncoding = null) {
		$request = new static();

		$request->setMethod('POST');
		$request->setUrl($url);

		$request->setFormData($formData, $formEncoding);

		return $request->exec();
	}

	protected function buildUrl() {
		if (! $this->url) {
			throw new \Exception("No url provided");
		}
		$out = $this->url;
		if ($this->query) {
			$out .= '?'.http_build_query($this->query);
		}
		return $out;
	}

}