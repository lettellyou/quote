<?php

function getData($data, $type) {
	$resultData = array();
	switch ($type) {
        case "international_future":     //国际期货
            /*
             * "hf_CT,hf_NID,hf_PBD,hf_SND,hf_ZSD,hf_AHD,hf_CAD,hf_S,hf_W,hf_C,hf_BO,hf_SM,hf_TRB,hf_HG,hf_NG,hf_CL,hf_SI,hf_GC,hf_LHC,hf_OIL,hf_XAU,hf_XAG,hf_XPT,hf_XPD"
              0	最新价
              1	涨跌幅
              2	买价
              3	卖价
              4	最高价
              5	最低价
              6	时间
              7	昨结算
              8	开盘价
              9	持仓量
              10
              11
              12	日期
              13	伦敦金
             *  */
            foreach ($data as $k => $v) {
                //$k = strtolower($k);
                $value = array();
                $value["new_price"] = $v[0]; //最新价
                //$value["fluctuation"] = round(($v[0]-$v[7]),2); ; //涨跌量
                $value["fluctuation"] = round(($v[1]*$v[7]/100),4); //涨跌量
                $value["frice_fluctuation"] = $v[1]; //涨跌幅
                $value["purchase_price"] = $v[2]; //买价
                $value["selling_price"] = $v[3]; //卖价
                $value["highest_price"] = $v[4]; //最高价
                $value["minimum_price"] = $v[5]; //最低价
                $value["time"] = $v[6]; //时间
                $value["settlement_yesterday"] = $v[7]; //昨结算
                $value["opening_price"] = $v[8]; //开盘价
                $value["open_interest"] = $v[9]; //持仓量
                $value["purchase_num"] = $v[10]; //买量
                $value["settlement_num"] = $v[11]; //卖量
                $value["date"] = $v[12]; //日期
                $value["name"] = $v[13]; //名称
                $value["code"] = $k; //代码
                $resultData[$k] = $value;
            }
            break;

        case "domestic_future":  //国内期货
            /*
             * 国内期货
             * "CU0,AL0, ZN0,NI0,RB0,  HC0,    BU0,SC0,RU0, JD0,PP0,  J0, JM,  I0,   ZC0,  TA0, MA0, AP0"
                沪铜,沪铝,沪锌,沪镍,螺纹钢,热轧卷板,沥青,原油,橡胶,鸡蛋,聚丙烯,焦炭,焦煤,铁矿石,动力煤，PTA，甲醇，苹果
             *  */
            foreach ($data as $k => $v) {
                //$k = strtolower($k);
                if($v) {
                    $value = array();
                    $value["contract_name"] = $v[0]; //合约名称
                    $value["time"] = $v[1]; //当前时间
                    $value["opening_price"] = $v[2]; //开盘价
                    $value["highest_price"] = $v[3]; //最高价
                    $value["minimum_price"] = $v[4]; //最低价
                    $value["selling_highest_price"] = $v[5]; //买卖最高价  *
                    $value["purchase_price"] = $v[6]; //买价
                    $value["selling_price"] = $v[7]; //卖价
                    $value["new_price"] = $v[8]; //最新价
                    $value["settlement"] = $v[9]; //结算价
                    $value["settlement_yesterday"] = $v[10]; //昨结算价
                    $value["purchase_num"] = $v[11]; //买量
                    $value["settlement_num"] = $v[12]; //卖量
                    $value["open_interest"] = $v[13]; //持仓量
                    $value["volume"] = $v[14]; //成交量
                    $value["exchange"] = $v[15]; //交易所简称
                    $value["category"] = $v[16]; //期货种类
                    $value["date"] = $v[17]; //当前日期
                    $value["fluctuation"] = round(($v[8]-$v[10]),2); //涨跌量
                    if($v[10]) {
                        $value["frice_fluctuation"] = round(($v[8]-$v[10])/$v[10]*100,2); //涨跌幅
                    }else {
                        $value["new_price"] = 0;
                    }
                    $value["code"] = $k; //代码
                    $resultData[$k] = $value;
                }
            }
            break;
		default :
			break;
	}
	return $resultData;
}
