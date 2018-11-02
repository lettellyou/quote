<?php

class getKFuture {

	private $URL;
	private $ISCACHE = false;
	private $CACHE_FILE;
	private $CACHE_DIR;
	private $GETTYPE = "get";
	private $TIMEOUT = 0;

	public function __construct($dir = "", $timeout = 0) {
		//$this->URL = "https://stock.sina.com.cn/futures/api/jsonp.php/"; //设置接口地址
		//$this->URL = "https://stock.sina.com.cn/futures/api/jsonp.php/kline=/InnerFuturesNewService.getFewMinLine?symbol="; //设置接口地址
		$this->URL = "https://stock.sina.com.cn/futures/api/jsonp.php/kline=/"; //设置接口地址
		if ($dir != "") {
			$this->ISCACHE = true; //开启缓存数据
			$this->CACHE_DIR = $dir; //设置缓存目录
			$this->TIMEOUT = $timeout; //设置缓存时间
			$this->createFolders($this->CACHE_DIR); //检查并创建缓存目录
		}
		//判断系统是否开启curl,开启则使用curl调用数据
		if (function_exists("curl_init")) {
			$this->GETTYPE = "curl";
		}
	}

	//获取json格式的行情数据  对getQuotes的封装
	public function getQuotesJson($code, $type = "Kline") {
		$data = $this->getQuotes($code, $type);
		return json_encode($data);
	}

	//获取数组形式的行情数据，对getQuotes的封装
	public function getQuotesArray($code, $type = "Kline") {
		return $this->getQuotes($code, $type);
	}

	//获取行情数据
	public function getQuotes($code,$time,$type = "Kline") {
		if ($this->ISCACHE) {
			$this->CACHE_FILE = $this->CACHE_DIR . $type . "_" .$time."_". md5($code) . ".php";
		}

		if ($this->ISCACHE && file_exists($this->CACHE_FILE) && time()-filemtime($this->CACHE_FILE) <= $this->TIMEOUT) {
			$data = include $this->CACHE_FILE;
		} else {
			$data = $this->getQuotesAndDecode($code,$time);
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
	private function getQuotesText($code,$time) {
        if(!$time) {
            //如果是日k，缓存时间设置为一天，
            $the_time = strtotime(date('Y-m-d',strtotime('+1 day'))) -time();
            $this->TIMEOUT = $the_time; //设置缓存时间
        }
		//$url = $this->URL . $code.'=/InnerFuturesNewService.getMinLine?symbol='.$code;
		if($time) {
            $url = $this->URL.'InnerFuturesNewService.getFewMinLine?symbol='.$code.'&type='.$time;
        }else {
			$url = $this->URL.'InnerFuturesNewService.getDailyKLine?symbol='.$code.'&_='.date('Y_m_d');
		}
		if ($this->GETTYPE == "curl") {
			return $this->curl($url);
		} else {
			return $this->get($url);
		}
	}

	//使用getQuotesText获取行情，并解码成需要的数组
	private function getQuotesAndDecode($code,$time) {
		$quotesArray = array();
		$codeArray = explode(",", $code);
		$text = $this->gb_to_utf8($this->getQuotesText($code,$time));
		$st =stripos($text,'[');
		$ed =stripos($text,']');
		$str=substr($text,($st+1),($ed-$st-1));
		$arr = explode('},{',$str);
		$data = [];
		foreach($arr as $k=> $v) {
			$temp = ltrim($v,'{');
			$temp = rtrim($temp,'}');
			$temp_arr = explode(',',$temp);
			$str = '';
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
