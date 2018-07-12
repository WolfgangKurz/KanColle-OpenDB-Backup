<?php
class Context {
	static function &getInstance(){
		static $_instance = null;
		if( $_instance===null ) $_instance = new static();
		return $_instance;
	}

	static function set($name, $value){
		$ctx = &Context::getInstance();
		$ctx->$name = $value;
	}
	static function get($name){
		$ctx = &Context::getInstance();
		return $ctx->$name;
	}
}

function _debug($text){
	if( !Context::get('cfg::debug') ) return null;
	return _display($text, 'debug');
}
function _info($text){ return _display($text, 'info'); }
function _warn($text){ return _display($text, 'warn'); }
function _error($text){ return _display($text, 'error'); }
function _normal($text){ return _display($text); }
function _verbose($text){ return _display($text); }

function _display($text, $type='normal'){
	switch($type){
		case 'error':
			$color = '31';
			break;
		case 'warn':
			$color = '33';
			break;
		case 'info':
			$color = '36';
			break;
		case 'debug':
			$color = '32';
			break;
		case 'normal':
		case 'verbose':
		default:
			$color = '0';
			break;
	}
	// TODO
	echo "\033[01;30m "
		. date('Y-m-d H:i:s') . ' > '
		. "\033[" . $color . "m "
		. $text
		. "\033[0m \n";
}

function _shell($cmd){
	_debug($cmd);
	return shell_exec($cmd);
}

function _available_binary($binary){
	return !empty( shell_exec('which ' . escapeshellarg($binary)) );
}
function _select_bzip(){
	if( Context::get('cfg::pbzip2') ) return 'pbzip2'; // pbzip2 available
	if( Context::get('cfg::bzip2') ) return 'bzip2'; // bzip2 available
	return null;
}

function _prepare_environment(){
	Context::set('cfg::bzip2', _available_binary('bzip2'));
	Context::set('cfg::pbzip2', _available_binary('pbzip2'));
}
