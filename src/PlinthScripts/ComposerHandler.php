<?php
namespace PlinthScripts;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class ComposerHandler {
	
	public static function copyDirectory($src, $dest) {
		
		if(!is_dir($src)) return false;
		if(!is_dir($dest)) {
			if(!mkdir($dest)) return false;
		}
		
		$i = new \DirectoryIterator($src);
		foreach ($i as $f) {
			if ($f->isFile()) {
				copy($f->getRealPath(), "$dest/" . $f->getFilename());
			} elseif (!$f->isDot() && $f->isDir()) {
				self::copyDirectory($f->getRealPath(), "$dest/$f");
			}
		}
		
	}
	
	public static function initProject(Event $event) {
	
		self::copyDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'project', getcwd());
	
	}
	
	public static function preInstall() {
		
		
		
	}
	
}