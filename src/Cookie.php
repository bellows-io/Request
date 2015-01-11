<?php

namespace Request;

class Cookie {

	protected $value;
	protected $expires;
	protected $domain;
	protected $path;
	protected $secure;
	protected $httpOnly;

	public function __construct($value, $expires, $domain, $path, $secure, $httpOnly) {

		$this->value = $value;
		$this->expires = $expires;
		$this->domain = $domain;
		$this->path = $path;
		$this->secure = $secure;
		$this->httpOnly = $httpOnly;
	}

	public function toHeaderString() {

	}

	public static function parse($cookieString) {
		if (! preg_match('/^Set-Cookie: (.+)$/', $cookieString, $matches)) {
			throw new \Exception("Invalid cookie format");
		}

		$tokens = array_map('trim', explode(";", $matches[1]));
		$value  = array_shift($tokens);

		$expires  = null;
		$domain   = null;
		$path     = null;
		$httpOnly = false;
		$secure   = false;

		foreach ($tokens as $i => $token) {
			$tokenVals = array_map('trim', split('=', $token));
			$count = count($tokenVals);
			if ($count == 2) {
				list($k, $v) = $tokenVals;
				switch($k) {
					case 'expires':
						$expires = strtotime($v);
						break;
					case 'domain':
						$domain = $v;
						break;
					case 'path':
						$path = $v;
						break;
				}
			} else if ($count == 1 && $i == count($tokens)) {
				switch (reset($tokenVals)) {
					case 'HttpOnly':
						$httpOnly = true;
						break;
					case 'secure':
						$secure = true;
						break;
				}
			}
		}

		return new Cookie($value, $expires, $domain, $path, $secure, $httpOnly);
	}

	public function getPublivalue() {
		return $this->value;
	}

	public function getExpires() {
		return $this->expires;
	}

	public function getDomain() {
		return $this->domain;
	}

	public function getPath() {
		return $this->path;
	}

	public function getSecure() {
		return $this->secure;
	}

	public function getHttpOnly() {
		return $this->httpOnly;
	}


}
