﻿<?php
/**
 * Get and parse information from external websites. Started by cron every 15 minutes
 * PHP Version 5
 *
 * @category File
 * @package  Crawler
 * @author   Malitsky Alexander <a.malitsky@gmail.com>
 * @license  GNU GENERAL PUBLIC LICENSE
 *
 */
date_default_timezone_set("UTC");
$currHour = date("G");
$nbsCrConf = array();
require_once dirname(__FILE__)."/nbs_conf.php";
if($nbsCrConf['stealthMode']){
    usleep (mt_rand(0,600)*1000000);  //we don't need english courtesy to be just in time
}
$time_start = microtime(true);
error_reporting(E_ALL);
ob_start();
require_once dirname(__FILE__)."/crawler_lb.php";
require_once dirname(__FILE__)."/db.php";
require_once dirname(__FILE__)."/output.php";

$buildings = array(
    array (1, 'http://novokosino.ndv.ru/sale/?build=1708'),
    array (2, 'http://novokosino.ndv.ru/sale/?build=1709'),
	array (3, 'http://novokosino.ndv.ru/sale/?build=1710')
	);
$snaps = array(); $fromdb = array(); $fromWeb = array();
$db = mysqli_init();
$db -> real_connect($nbsCrConf['dbServer'], $nbsCrConf['dbLogin'], $nbsCrConf['dbPassword'], $nbsCrConf['dbName']);
if ($db -> connect_errno) {
	echo "<p>Error: Failed to connect to MySQL: (".$db->connect_errno.") ".$db->connect_error ."</p>\r\n"; }
$db->query("SET time_zone = '+0:00'");
for ($i = 0; $i < count($buildings); $i++){
    crawlerR9mk($db, $buildings[$i][1], $buildings[$i][0]);
}
$db -> close();
$output = ob_get_contents();
$ifErrors = stripos($output , 'error') || stripos($output, 'warning');
$currMinute = date("i");
if($ifErrors !== false || ($currHour == 20 && $currMinute >= 30 && $currMinute < 45)){
    sendMailNotice($output, $ifErrors, $nbsCrConf['pearMail'], $nbsCrConf['serverName']);
};
$time_end = microtime(true);
echo "<p class='execTime'>Execution time: ".round($time_end - $time_start, 2)." s.</p>";
saveLog(ob_get_contents());
ob_end_clean();
