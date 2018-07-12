<?php
require(dirname(__FILE__) . '/db-utils.php');

/////////////////////////////////////////////////////////////////////////////////

// Prepare environment
_prepare_environment();

$cfg_debug = false;
$cfg_single = true;

$ver = '2';
_warn('KanColle OpenDB Backup Utility - Restore');
_warn(':: rev ' . $ver);
_warn(':: bzip2 available: ' . (Context::get('cfg::bzip2') ? 'yes' : 'no'));
_warn(':: pbzip2 available: ' . (Context::get('cfg::pbzip2') ? 'yes' : 'no'));
_warn('');

if( _select_bzip()===null ){
	_error('bzip2 or pbzip2 required!');
	return;
}

foreach($argv as $v){
	$opt = strtolower(substr($v, 2));
	if( substr($v, 0, 2) != '--' ) continue;

	switch( $opt ){
		case 'debug':
			$cfg_debug = true;
			break;

		case 'single':
			$cfg_single = true;
			break;
		case 'each':
			$cfg_single = false;
			break;

		case 'help':
			_info('Available parameters:');
			_info('  --help : Display this help and exit');
			_info('  --debug : Display debug logs');
			_info('  --single : Save the result as single sql file');
			_info('  --each : Save the results as each sql file. Filename is based on backup directory name');
			break;

		default:
			_error('unrecognized option "' . $v . '"');
			_error('Try with "--help" for more information.');
			break;
	}
}
Context::set('cfg::debug', $cfg_debug);

chdir( dirname(__FILE__) );
date_default_timezone_set('UTC');
$DATE = date('Y-m-d_H-i-s');

if( is_file('opendb-backup-' . $DATE . '.sql') ){
	_warn('Restored file "opendb-backup-' . $DATE . '.sql" already exists, delete it');
	_shell('rm -f "opendb-backup-' . $DATE . '.sql"');
}

_info('Selecting available backup files...');
$backups = array();
$dir = opendir('./');
if( !$dir ){
	_error('Cannot read directory "./", check permissions.');
	return;
}
while( ($d = readdir($dir))!==false ){
	if( $d[0]=='.' ) continue;
	if( !is_dir('./' . $d) ) continue; // not directory

	$backups[$d] = array();

	$dir_inner = opendir('./' . $d);
	if( !$dir_inner ){
		_error('Cannot read directory "./' . $d . '", check permissions.');
		closedir($dir);
		return;
	}
	while( ($di = readdir($dir_inner))!==false ){
		if( $di[0]=='.' ) continue;

		if( !fnmatch( 'opendb-backup.sql.bz2.*', $di) ) continue; // not match
		$backups[$d][] = $di;
	}
	closedir($dir_inner);

	_debug('Found ' . count($backups[$d]) . ' files in "' . $d . '"');

	natsort($backups[$d]);
}
closedir($dir);

_debug('Excluding not backup data containing directories...');
$backups = array_filter($backups, function($x){
	return count($x)>0;
});

_debug('Sorting backup list...');
uksort($backups, function($a, $b){
	return strnatcmp($a, $b);
});

$index = 0;
$count = count($backups);

_info('Concat and Unzipping...');
foreach($backups as $date => $list){
	_verbose(' start "' . $date . '" (' . ++$index . '/' . $count . ')');

	$cmd = 'cat ./' . $date . '/opendb-backup.sql.bz2.* | ' . _select_bzip() . ' -d';
	if( $cfg_single )
		$cmd .= ' >> "opendb-backup-' . $DATE . '.sql"';
	else
		$cmd .= ' > "opendb-backup-' . $date . '.sql"';

	_shell($cmd);
}

_info('Done');
