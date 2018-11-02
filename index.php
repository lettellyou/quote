<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/30
 * Time: 18:08
 */

@header("Access-Control-Allow-Origin:*");
@header("Content-type: text/html; charset=utf-8");
$codeArray = include "list.inc.php";
include_once 'sinaFinanceQuotes.php';
include_once 'getData.php';
$type = isset($_REQUEST['type'])?intval($_REQUEST['type']):2;    //类型  1.国际期货   2.国内期货
$pro_type = isset($_REQUEST['type'])?intval($_REQUEST['type']):'';    //类型  1.国际期货   2.国内期货
$list = isset($_REQUEST['list'])?trim($_REQUEST['list']):'';    //产品代码
//$show = isset($_REQUEST['show'])?trim($_REQUEST['show']):'json';    //返回数据格式   debug:原始数据   xml   json
if(!isset($list)){
    $result['status'] = "04";
    $result['msg'] = "您未输入查询内容！";
    echo json_encode($result);
    exit();
}else{
    $data=array();
    $list=explode(',',$list);
    foreach ($list as $key=>$value){
        $data[$key] = $value.',';
    }
}

if (empty($data)) {
    $result['status'] = "04";
    $result['msg'] = "您获取的数据暂无法获取！";
    echo json_encode($result);
    exit();
} else {
    foreach ($data as $key=>$value){
        if($pro_type) {
            $type = $pro_type;
        } else {
            if(strtolower(substr($value,0,3)) == 'hf_') {
                $type = 1;
            } else {
                $type = 2;
            }
        }

        if($type == 1) {
            if( strtolower(substr($value,0,3)) != 'hf_') {
                $value = 'hf_'.strtoupper($value);
            } else {
                $value = 'hf_'.strtoupper(substr($value,3,-1));
            }
        } else {
            $value = strtoupper($value);
        }
        $data[$key]=rtrim($value,',');
    }
}
$cacheFile = dirname(__FILE__) . "/cache/";
$Quotes = new sinaFinanceQuotes($cacheFile, 1);
$result['status'] = "00";
$result['msg'] = "数据获取成功！";

foreach ($data as $key=>$value){
    $res = $Quotes->getQuotes($value);
    if(strtolower(substr($value,0,3)) == 'hf_') {
        $resultData = getData($res, 'international_future');
    } else {
        $resultData = getData($res, 'domestic_future');
    }
    $result['data'][$key] =$resultData;
}

$data = array();
foreach ($result['data'] as $key => $value) {
    foreach ($value as $k=>$v){
        $data[$key] = $v;
    }
}
$result['data'] = $data;
echo json_encode($result);
exit();