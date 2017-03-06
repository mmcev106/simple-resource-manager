<?php namespace Mmcev106\SimpleResourceManager;

class SimpleResourceManager
{
	private $rootDir;

	function __construct($rootDir){
		$this->rootDir = $rootDir;
	}

	function get($path){
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		if(substr($path,0,8) == "https://") {
			$url = $path;
		}
		else {
			$fullLocalPath = $this->rootDir . "/$path";

			// Add the filemtime to the url for cache busting.
			clearstatcache(true, $path);
			$url = $path . '?' . filemtime($fullLocalPath);
		}

		if ($extension == 'css') {
			return "<link rel='stylesheet' type='text/css' href='" . $url . "'>";
		}
		else if ($extension == 'js') {
			return "<script src='" . $url . "'></script>";
		}
		else {
			throw new Exception('Cannot add unsupported file type: ' . $path);
		}
	}
}