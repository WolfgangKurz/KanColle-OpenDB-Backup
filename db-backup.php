<?php
require(dirname(__FILE__) . '/db-utils.php');

if( is_file(dirname(__FILE__) . '/db-backup-cfg.php') )
	require(dirname(__FILE__) . '/db-backup-cfg.php');
else
	require(dirname(__FILE__) . '/../db-backup-cfg.php');

/////////////////////////////////////////////////////////////////////////////////

// Prepare environment
_prepare_environment();

$cfg_mode = null;
$cfg_debug = false;

$ver = '4';
_warn('KanColle OpenDB Backup Utility - Backup');
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
		case 'full':
			$cfg_mode = true;
			break;
		case 'incremental':
			$cfg_mode = false;
			break;

		case 'debug':
			$cfg_debug = true;
			break;

		case 'help':
			_info('Available parameters:');
			_info('  --help : Display this help and exit');
			_info('  --debug : Display debug logs');
			_info('  --full : Do full backup');
			_info('  --incremental : Do incremental backup');
			break;

		default:
			_error('unrecognized option "' . $v . '"');
			_error('Try with "--help" for more information.');
			break;
	}
}
if($cfg_mode===null){
	_error('Need to set mode (--full, --incremental)');
	return;
}

Context::set('cfg::mode', $cfg_mode);
Context::set('cfg::debug', $cfg_debug);

chdir( dirname(__FILE__) );
date_default_timezone_set('UTC');
$DATE = date('Y-m-d_H-i-s');

// Change working directory
_info('Preparing backup directory');

if( is_dir($DATE) ){
	_warn('Backup "' . $DATE . '" already exists, delete it');
	_shell('rm -rf "' . $DATE . '/"');
}

_info('Backup directory is "' . $DATE . '"');
mkdir($DATE);
chdir($DATE);

// Dump and compress
if($cfg_mode){
	_verbose('Full backup mode (no binlogs)');

	// Purge binlogs
	_shell('mysql -u' . $cfg_id . ' -p' . $cfg_pw . ' -e"RESET MASTER;"');

	// Flush tables
	_shell('mysql -u' . $cfg_id . ' -p' . $cfg_pw . ' -e"FLUSH TABLES WITH READ LOCK;"');

	// Dump
	_info('Dumping...');
	_shell('mysqldump -u' . $cfg_id . ' -p' . $cfg_pw . ' --single-transaction opendb > opendb-backup.sql');
}else{
	_verbose('Incremental backup mode (with binlogs)');

	_info('Flush binlogs first');
	_shell('mysql -u' . $cfg_id . ' -p' . $cfg_pw . ' -e"FLUSH LOGS;"');

	_info('Copy binlogs from "' . dirname($cfg_binlog . '*') . '"');
	$binlogs = array();
	$path = dirname($cfg_binlog);
	$dir = opendir($path); // get binlogs list
	if( !$dir ){
		_error('Cannot read directory "' . $path . '", check permissions.');
		return;
	}
	while( ($d = readdir($dir))!==false ){
		if( $d[0]=='.' ) continue;
		if( !fnmatch( basename($cfg_binlog . '*'), $d) ) continue; // not match
		if( $d == basename($cfg_binlog . 'index') ) continue; // not index

		$binlogs[] = $d;
	}
	closedir($dir);

	// sort with filename (numbering)
	_debug('Sorting binlog files');
	sort($binlogs);

	// not latest file
	_debug('Exclude latest binlog');
	array_pop($binlogs);

	foreach($binlogs as $binlog){
		_info('Converting binlog "' . $binlog . '"...');

		_shell('mysqlbinlog -s "' . $path . '/' . $binlog . '" >> ./opendb-backup.sql');

		_debug('Delete original binlog "' . $binlog . '"');
		unlink($path . '/' . $binlog);
	}
}
_info('Dump done');

_info('Compressing...');
_shell(_select_bzip() . ' -p2 -m50 ./opendb-backup.sql');

// Split dump to 50mb files
_info('Split compressed backup files as ' . $cfg_split);
_shell('split -b ' . $cfg_split . ' ./opendb-backup.sql.bz2 ./opendb-backup.sql.bz2.');

// Remove original dump file (compressed)
_info('Delete original compressed backup file');
unlink('./opendb-backup.sql.bz2');

// Change back working directory to git base
_debug('Change back cwd to git base');
chdir('..');

// Commit and push git
_info('Commit and Push');
_shell('git add .');
_shell('git commit -a -m "' . $DATE . ' ' . ($cfg_mode ? 'full' : 'incremental') . ' backup"');
_shell('git push');

_info('Done');
