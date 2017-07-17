<?php

class SimpleResourceManager
{
	const WORDPRESS_PLUGINS_DIR = '/wp-content/plugins/';

	private $wordpress = false;

	function __construct($baseDir, $baseUrl = null)
	{
		$baseDir = str_replace('\\', '/', $baseDir);
		$this->baseDir = $baseDir;

		if ($baseUrl != null) {
			$this->baseUrl = $baseUrl;
		}
		else {
			$this->wordpress = true;
			$pluginsIndex = strpos($baseDir, self::WORDPRESS_PLUGINS_DIR);
			if ($pluginsIndex !== false) {
				$this->baseUrl = plugins_url(substr($baseDir, $pluginsIndex + strlen(self::WORDPRESS_PLUGINS_DIR)));
			}
			else if (strpos($baseDir, '/wp-content/themes/') !== false) {
				$this->baseUrl = get_template_directory_uri();
			}
			else {
				self::throwException("You must specify as baseUrl for the following baseDir: $baseDir");
			}
		}
	}

	function add($relativePath, $deps = null)
	{
		if(strpos($relativePath, 'http://') === 0 ||
		   strpos($relativePath, 'https://') === 0){
			$url = $relativePath;
			$mtime = null; // Assume this is a versioned CDN url and requires no cache busting.
		}
		else{
			$absolutePath = $this->baseDir . '/' . $relativePath;
			if (!file_exists($absolutePath)) {
				self::throwException("The specified file could not be found: $relativePath");
			}

			clearstatcache(true, $absolutePath);
			$mtime = filemtime($absolutePath);
			$url = "$this->baseUrl/$relativePath?$mtime";
		}

		$extension = pathinfo($relativePath, PATHINFO_EXTENSION);

		$enqueueFunction = function($relativePath, $url) use ($extension){
			if ($extension == 'css') {
				echo "<link href='$url' rel='stylesheet'>";
			}
			else if ($extension == 'js') {
				echo "<script src='$url'></script>";
			}
			else {
				self::throwUnknownExtensionException($relativePath);
			}
		};

		if($this->wordpress){
			if ($extension == 'css') {
				$enqueueFunction = 'wp_enqueue_style';
			}
			else if ($extension == 'js') {
				$enqueueFunction = 'wp_enqueue_script';

				if($deps == null){
					// Many WordPress plugins will require jQuery, so we might as well make it a dependency by default.
					$deps = ['jquery'];
				}
			}
			else {
				self::throwUnknownExtensionException($relativePath);
			}
		}

		$enqueueFunction($relativePath, $url, $deps, $mtime);
	}

	private function throwException($message)
	{
		throw new Exception(__CLASS__ . ': ' . $message);
	}

	private function throwUnknownExtensionException($relativePath)
	{
		self::throwException("Unknown extension for file: $relativePath");
	}
}