<?php

/**
 * @file plugins/generic/clamAV/Clamd.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Copyright (c) 2011 Hiroaki Kawai <hiroaki.kawai@gmail.com>
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_clamAV
 * @brief ClamAV utility for scanning uploaded files.
 *
 * @category Networking
 * @package Net_Clamd
 * @author Hiroaki Kawai <hiroaki.kawai@gmail.com>
 *
 * NOTE: FILDES (FILe-DEScriptor based communitation) is not supported because handling file descriptor is difficult.
 * NOTE: IDSESSION, END is not supported by design. This library focuses on single file scan.
 *
 * Simple example
 * <code>
 * <?php
 * ini_set('error_reporting',E_ALL);
 * $c = new Net_Clamd('unix:///tmp/clamd.socket');
 * var_dump($c->ping());
 * var_dump($c->version());
 * var_dump($c->reload());
 * var_dump($c->scan("/var/tmp/src/php-5.2.13RC1.tar.bz2"));
 * var_dump($c->instream("hogehoge"));
 * var_dump($c->stats());
 * var_dump($c->shutdown());
 * </code>
 *
 */

class Net_Clamd {
	private $_hostname;
	private $_port;
	private $_timeout;

	/**
	 * @param $hostname string hostname, tcp://hostname or unix://path
	 * @param $port integer This value will be ignored with unix domain socket.
	 * @param $timeout integer Timeout seconds. default is default_socket_timeout ini value.
	 */
	function __construct($hostname, $port=3310, $timeout=null){
		$this->_hostname = $hostname;
		$this->_port = $port;
		if(substr($hostname,0,7)=='unix://'){
			$this->_port = -1;
		}
		$this->_timeout = $timeout;
		if($timeout===null){
			$this->_timeout = ini_get("default_socket_timeout");
		}
	}

	private function _open(){
		if($f = fsockopen($this->_hostname, $this->_port, $errno, $errstr, $this->_timeout)){
			return $f;
		}
		trigger_error($errstr.' ('.$errno.')', E_USER_ERROR);
		return false;
	}

	private function _read($f){
		$r='';
		while(($t=fread($f, 8192))!==false){
			if(!strlen($t)){ break; }
			$r.=$t;
		}
		$x = explode("\0",$r,2);
		if(count($x)==2){
			return $x[0];
		}
		trigger_error('clamd response is not NULL terminated.');
		return $r;
	}

	/**
	 * Issue PING command.
	 * @return string "PONG" on success. false on failure.
	 */
	function ping(){
		if($f = $this->_open()){
			fwrite($f, "zPING\0");
			$r = $this->_read($f);
			fclose($f);
			return $r;
		}
		return false;
	}

	/**
	 * Issue VERSION command.
	 * @return string Version string like "ClamAV 0.95.3/10442/Wed Feb 24 07:09:42 2010"
	 */
	function version(){
		if($f = $this->_open()){
			fwrite($f, "zVERSION\0");
			$r = $this->_read($f);
			fclose($f);
			return $r;
		}
		return false;
	}

	/**
	 * Issue RELOAD command.
	 * @return string
	 */
	function reload(){
		if($f = $this->_open()){
			fwrite($f, "zRELOAD\0");
			$r = $this->_read($f);
			fclose($f);
			return $r;
		}
		return false;
	}

	/**
	 * Issue SHUTDOWN command.
	 * @return string empty string on success or false.
	 */
	function shutdown(){
		if($f = $this->_open()){
			fwrite($f, "zSHUTDOWN\0");
			$r = $this->_read($f);
			fclose($f);
			return $r;
		}
		return false;
	}

	/**
	 * Issue SCAN/RAWSCAN/CONTSCAN/MULTISCAN command.
	 * @param $abspath string absolute path (file or directory)
	 * @param $mode string One of ["", "RAW", "CONT", "MULTI"]. Default is "MULTI".
	 * @return string "$abspath: OK" will be returned if OK. false on failure.
	 */
	function scan($abspath, $mode= 'MULTI'){
		if(!in_array($mode, array('','RAW','CONT','MULTI'))){
			trigger_error("invalid mode ".$mode, E_USER_ERROR);
			return false;
		}
		if($f = $this->_open()){
			fwrite($f, "z".$mode."SCAN ".$abspath."\0");
			$r = $this->_read($f);
			fclose($f);
			return $r;
		}
		return false;
	}

	/**
	 * Issue INSTREAM command.
	 * @param $data string the data to test
	 * @return string "stream: OK" will be returned if OK. false on failure.
	 */
	function instream($data){
		if($f = $this->_open()){
			fwrite($f, "zINSTREAM\0");
			if(strlen($data)>0){
				fwrite($f, pack("N",strlen($data)).$data);
			}
			fwrite($f, pack("N",0)); // chunk termination
			$r = $this->_read($f);
			fclose($f);
			return $r;
		}
		return false;
	}

	/**
	 * Issue INSTREAM command against a file name.
	 * @param $file string the data to test
	 * @return string "stream: OK" will be returned if OK. false on failure.
	 */
	function filestream($file){
		$handle = fopen($file, "rb");
		if($f = $this->_open()){
			fwrite($f, "zINSTREAM\0");
			while (!feof($handle)) {
				$data = fread($handle, 8192);
				fwrite($f, pack("N",strlen($data)).$data);
			}
			fclose($handle);
			fwrite($f, pack("N",0)); // chunk termination
			$r = $this->_read($f);
			fclose($f);
			return $r;
		}
		return false;
	}

	/**
	 * Issue STATS command.
	 * @return string The status information of clamd.
	 */
	function stats(){
		if($f = $this->_open()){
			fwrite($f, "zSTATS\0");
			$r = $this->_read($f);
			fclose($f);
			return $r;
		}
		return false;
	}
}
