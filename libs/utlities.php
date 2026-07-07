<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
require_once "jdf.php";
function get_user_transactions($PersonID = '',$dateFrom='',$dateTo='')
{
    global  $wpdb;
    $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}bazara_transactions bz where bz.PersonId = %s AND bz.Deleted = 0 AND CASE WHEN %s <> '' THEN (DATE(bz.Date) >= DATE(%s) AND DATE(bz.Date) <= DATE(%s)) ELSE -1 END ORDER BY bz.ROW ASC",$PersonID,$dateFrom,$dateFrom,$dateTo) ;
	return $wpdb->get_results($query);
}
function mahak_price($price){
	if(empty($price))
		return 0;
	return number_format($price);
}
function mahak_status($status){
	if(empty($status))
		return 'بی حساب';
	return $status == 1 ? 'بدهکار' : ($status == 2 ? 'بستانکار' : 'بی حساب');
}
function mahak_jdf($date){
	if(empty($date))
		return '';
	return jdate('Y/m/d',strtotime($date));
}
function jalali_to_timestamp($date, $first = true)
{
	date_default_timezone_set('Asia/Tehran');
	$time = explode('/', $date);
	$gregorian = jalali_to_gregorian($time[0], $time[1], $time[2], '/');
	/* $gregorian = explode('/', $gregorian);
	 if ($first)
		 $timeNow = mktime(0, 0, 0, $gregorian[1], $gregorian[2], $gregorian[0]);
	 else
		 $timeNow = mktime(23, 59, 59, $gregorian[1], $gregorian[2], $gregorian[0]);*/

	return $gregorian;
}


function jalali_to_datetimestamp($date, $first = true)
{
	date_default_timezone_set('Asia/Tehran');
	$time = explode('/', $date);
	$gregorian = jalali_to_gregorian($time[0], $time[1], $time[2], '/');
	/* $gregorian = explode('/', $gregorian);
	 if ($first)
		 $timeNow = mktime(0, 0, 0, $gregorian[1], $gregorian[2], $gregorian[0]);
	 else
		 $timeNow = mktime(23, 59, 59, $gregorian[1], $gregorian[2], $gregorian[0]);*/

	return $gregorian . ' ' .date('H:i:s',time());
}