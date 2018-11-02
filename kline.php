<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 9:27
 */
@header("Access-Control-Allow-Origin:*");
@header("Content-type: text/html; charset=utf-8");

$codeArray = include "list.inc.php";
include_once 'getKFuture.php';
include_once 'getData.php';
include_once 'getKMin.php';
include_once 'getInternationKMin.php';

$time = isset($_REQUEST['time']) ? intval(trim($_REQUEST['time'])) : '';
$type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 2; //类型  1.国际期货   2.国内期货
$list = isset($_REQUEST['list']) ? trim($_REQUEST['list']) : ''; //产品代码

$resultData = array();
$result = array(
	"status" => "01",
	"msg" => "参数错误，获取数据失败",
	"data" => $resultData,
);

if (!isset($list)) {
	$result['status'] = "04";
	$result['msg'] = "您未输入查询内容！";
	echo json_encode($result);
	exit();
}

$cacheFile = dirname(__FILE__) . "/cache/";
if ($type == 2) {
	if ($time && !in_array($time, [1, 5, 15, 30, 60])) {
		$result['status'] = "04";
		$result['msg'] = "请输入正确的time参数！";
		echo json_encode($result);
		exit();
	}
	if ($time == 1) {
		$Quotes = new getKMin($cacheFile, 1);
	} else {
		if (!$time) {
			//如果是日k，缓存时间设置为一天，
			$timeout = strtotime(date('Y-m-d', strtotime('+1 day'))) - time();
		} else {
			$timeout = 1;
		}
		$Quotes = new getKFuture($cacheFile, $timeout);
	}
	$time1 = $time;
} else {
	if ($time && !in_array($time, [1, 5, 15, 30, 60])) {
		$result['status'] = "04";
		$result['msg'] = "请输入正确的time参数！";
		echo json_encode($result);
		exit();
	}
	if (in_array($time, [5, 15, 30, 60])) {
		$time1 = 5;
	} else {
		$time1 = $time;
	}
	$Quotes = new getInternationKMin($cacheFile, 1, $time1);
}

$result['status'] = "00";
$result['msg'] = "数据获取成功！";

$res = $Quotes->getQuotes($list, $time1);

if (in_array($time, [5, 15, 30, 60]) && $type == 1) {
	//手动造国际期货分钟k线
	$data = json_decode($res, true);
	$res = [];
	$maxPrice = 0;
	$minPrice = 0;
	$openPrice = 0;
	$closePrice = 0;
	$num = 0;
	if ($data) {
		//var_export($data);
		foreach ($data['result'] as $key => $list) {
			foreach ($list as $k => $v) {
				$arr = explode(',', $v);
				if (!$k)
					$minPrice = $arr[1];
				if ($maxPrice < $arr[1])
					$maxPrice = $arr[1];
				if ($minPrice > $arr[1])
					$minPrice = $arr[1];
				/*
				  if ($k % $time == 0 && $k) {
				  $openPrice = $arr[1];
				  $res[] = date('Y-m-d H:i', strtotime($key . ' ' . $arr[0]) - $time * 60) . ',' . $openPrice . ',' . $maxPrice . ',' . $minPrice . ',' . $closePrice . ',' . $num;
				  $maxPrice = $arr[1];
				  $minPrice = $arr[1];
				  $num = 0;
				  }

				 */
				if ($k % $time == 0) {
					$openPrice = $arr[1];
					$minPrice = $arr[1];
				}

				if ($k % $time == $time - 1 && $k > 0) {
					$closePrice = $arr[1];
					$res[] = date('Y-m-d H:i', strtotime($key . ' ' . $arr[0]) - $time * 60) . ',' . $openPrice . ',' . $maxPrice . ',' . $minPrice . ',' . $closePrice . ',' . $num;
					$maxPrice = 0;
					$minPrice = 0;
					$num = 0;
				}
				$num += $arr[2];
			}
		}
	}
	if ($time == 5 && $type == 1) {
		$tmp=$res;
		$res=array_slice($tmp, count($tmp)-200-1);
	}
	$res = json_encode(['code' => 000000, 'message' => 'success', 'columns' => '交易时间,开盘价,最高价,最低价,收盘价,成交量', 'result' => $res]);
}
echo $res;
