<?php

class getInternationKMin {

    private $URL;
    private $ISCACHE = false;
    private $CACHE_FILE;
    private $CACHE_DIR;
    private $GETTYPE = "get";
    private $TIMEOUT = 0;

    public function __construct($dir = "", $timeout = 0,$time) {
        if($time == 1) {
            $this->URL = "http://stock2.finance.sina.com.cn/futures/api/json.php/GlobalFuturesService.getGlobalFuturesMinLine"; //设置接口地址
        } else if($time == 5){
            $this->URL = "http://stock2.finance.sina.com.cn/futures/api/json.php/GlobalFuturesService.getGlobalFutures".$time."MLine"; //设置接口地址
        } else {
            $this->URL = "http://stock2.finance.sina.com.cn/futures/api/json.php/GlobalFuturesService.getGlobalFuturesDailyKLine"; //设置接口地址
        }
        if ($dir != "") {
            $this->ISCACHE = true; //开启缓存数据
            $this->CACHE_DIR = $dir; //设置缓存目录
            $this->TIMEOUT = $timeout; //设置缓存时间
            if(!$time) {
                //如果是日k，缓存时间设置为一天，
                $the_time = strtotime(date('Y-m-d',strtotime('+1 day'))) -time();
                $this->TIMEOUT = $the_time; //设置缓存时间
            }
            $this->createFolders($this->CACHE_DIR); //检查并创建缓存目录
        }
        //判断系统是否开启curl,开启则使用curl调用数据
        if (function_exists("curl_init")) {
            $this->GETTYPE = "curl";
        }
    }

    //获取json格式的行情数据  对getQuotes的封装
    public function getQuotesJson($code, $type = "Dline") {
        $data = $this->getQuotes($code, $type);
        return json_encode($data);
    }

    //获取数组形式的行情数据，对getQuotes的封装
    public function getQuotesArray($code, $type = "Kline") {
        return $this->getQuotes($code, $type);
    }

    //获取行情数据
    public function getQuotes($code,$time) {

        if ($this->ISCACHE) {
            $this->CACHE_FILE = $this->CACHE_DIR . $time . "_" . md5($code) . ".php";
        }
        if ($this->ISCACHE && file_exists($this->CACHE_FILE) && time()-filemtime($this->CACHE_FILE) <= $this->TIMEOUT) {
            $data = include $this->CACHE_FILE;
        } else {
            if ($time) {
                $data = $this->getQuotesAndDecode($code,$time);
            } else {
                $data = $this->getQuotesAndDecodeDay($code);
            }
            if ($data) {
                $text = "<?php  return ";
                $text.= var_export($data, true);
                $text.= ";";
                file_put_contents($this->CACHE_FILE, $text, LOCK_EX);
            }
        }
        return $data;
    }

    //通过code，从sina获取原始数据,自动判断使用curl还是file_get_contents
    private function getQuotesText($code) {
        $url = $this->URL .'?symbol='.$code;
        if ($this->GETTYPE == "curl") {
            return $this->curl($url);
        } else {
            return $this->get($url);
        }
    }

    //使用getQuotesText获取行情，并解码成需要的数组
    private function getQuotesAndDecode($code,$time) {
        $text = $this->gb_to_utf8($this->getQuotesText($code));
        if($time == 1) {
            $st =stripos($text,'({minLine_1d:');
            $ed =stripos($text,'})');
            $str=substr($text,($st+strlen('({minLine_1d:')),$ed-strlen('({minLine_1d:'));
            $data = array();
            $arr = json_decode($str,true);
            foreach($arr as $k=> $v) {
                $str = '';
                foreach($v as $key=> $val) {
                    $str .= $val.',';
                }
                $data[] = rtrim($str,',');
            }
            $arr = explode(',',$data[0]);
            $data[0] = $arr[4].','.$arr[5].','.$arr[6];
        } else if($time == 5) {
            $scode = '({'.$code.':';
            $st =stripos($text,$scode);
            $ed =stripos($text,'})');
            $str=substr($text,($st+strlen($scode)),$ed-strlen($scode));
            $data = array();
            $arr = json_decode($str,true);
            foreach ($arr as $i=> $list) {
                foreach($list as $k=> $v) {
                    $str = '';
                    foreach($v as $key=> $val) {
                        $str .= $val.',';
                    }
                    if(!$k && !$i) {
                        $list_arr = explode(',',rtrim($str,','));
                        $data[$list[0][0]][] = $list_arr[2].','.$list_arr[3].','.$list_arr[4];
                    } else if(!$k){
                        $list_arr = explode(',',rtrim($str,','));
                        $data[$list[0][0]][] = $list_arr[1].','.$list_arr[2].','.$list_arr[3];
                    } else {
                        $data[$list[0][0]][] = rtrim($str,',');
                    }
                }
            }
        }

        $info = array();
        $info['code'] = '000000';
        $info['message'] = 'success';
        $info['columns'] = '交易时间,价格,--';
        $info['result'] = $data;
        return json_encode($info);
    }

    //使用getQuotesText获取行情，并解码成需要的数组  日线
    private function getQuotesAndDecodeDay($code) {
        $quotesArray = array();
        $codeArray = explode(",", $code);
        $text = $this->gb_to_utf8($this->getQuotesText($code));
        /*$st =stripos($text,'[');
        $ed =stripos($text,']');
        $str=substr($text,($st+1),($ed-$st-1));*/
        $arr = explode('},{',$text);
        $data = [];
        foreach($arr as $k=> $v) {
            $temp = ltrim($v,'{');
            $temp = rtrim($temp,'}');
            $temp_arr = explode(',',$temp);
            $str = '';
            $the_time = strtotime(rtrim(explode(':"',$temp_arr[0])[1],'"'));
            if($the_time <= strtotime("-6 month")) continue;
            foreach($temp_arr as $key=> $val) {
                $str .= rtrim(explode(':"',$val)[1],'"').',';
            }
            $data[] = rtrim($str,',');
        }
        //$data['result'] = rtrim($str,',');;
        $info['code'] = '000000';
        $info['message'] = 'success';
        $info['columns'] = '交易时间,开盘价,最高价,最低价,收盘价,成交量';
        $info['result'] = $data;
        return json_encode($info);
    }

    //curl获取内容
    public function curl($url, $params = "", $header = array(), $method = 'GET', $multi = false) {
        $opts = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $header
        );

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                if ($params != "") {
                    $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                } else {
                    $opts[CURLOPT_URL] = $url;
                }
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                return "";
            //break;
            //throw new Exception('不支持的请求方式！');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            return "";
        }
        ///throw new Exception('请求发生错误：' . $error);
        return $data;
    }

    //file_get_contents获取url内容
    public function get($url) {
        ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; 4399Box.560; .NET4.0C; .NET4.0E)');
        return file_get_contents($url);
    }

    //utf8转码成gb2312
    public function utf8_to_gb($value) {
        $value_1 = $value;
        $value_2 = @iconv("utf-8", "gb2312//IGNORE", $value_1);
        $value_3 = @iconv("gb2312", "utf-8//IGNORE", $value_2);
        if (strlen($value_1) == strlen($value_3)) {
            return $value_2;
        } else {
            return $value_1;
        }
    }

    //gb2312转码成utf8
    public function gb_to_utf8($value) {
        $value_1 = $value;
        $value_2 = @iconv("gb2312", "utf-8//IGNORE", $value_1);
        $value_3 = @iconv("utf-8", "gb2312//IGNORE", $value_2);
        if (strlen($value_1) == strlen($value_3)) {
            return $value_2;
        } else {
            return $value_1;
        }
    }

    //递归创建文件夹
    public function createFolders($dir) {
        return is_dir($dir) or ( $this->createFolders(dirname($dir)) and mkdir($dir, 0777));
    }

}
