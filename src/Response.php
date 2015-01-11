<?php

namespace Request;

class Response {

	protected $status;
	protected $contentType;
	protected $cookies;
	protected $body;
	protected $headers;
	protected $raw;

	public function __construct($raw, $body, array $headers, $status, $contentType, array $cookies = array()) {

		$this->status = $status;
		$this->contentType = $contentType;
		$this->cookies = $cookies;
		$this->body = $body;
		$this->headers = $headers;
		$this->raw = $raw;

	}

	public function getStatus() {
		return $this->status;
	}

	public function getContentType() {
		return $this->contentType;
	}

	public function getCookies() {
		return $this->cookies;
	}

	public function getBody() {
		return $this->body;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getRaw() {
		return $this->raw;
	}


}