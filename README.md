行情接口：
接口地址：http://ip/index.php?type=TYPE&list=LIST
参数：TYPE  类型，可取值   1：国际期货，   2：国内期货，    默认值：2
	LIST  产品代码，支持传多个参数，用英文逗号分割，建议大写

返回数据为json格式，
 Status: 状态码  msg：描述   data：数据
new_price：最新价，fluctuation：张跌量，frice_fluctuation：涨跌幅，purchase_price：买价，selling_price：卖价，highest_price：最高价，minimum_price：最低价，time：时间，settlement_yesterday：昨结算，opening_price：开盘价，open_interest：持仓量，date：日期， code ：产品代码

K线：
接口地址：http://ip/kline.php?type=TYPE&list=LIST&time=TIME
参数：TYPE  类型，可取值   1：国际期货，   2：国内期货，    默认值：2
	LIST  产品代码，只支持单个参数
TIME：可空，为空时返回的是日线
	当type为1时，time可取值为1,2,4,5,6和7，分别表示分时、5日分时、5分钟k、15分钟k、30分钟k、60分钟k和日k
当type为2时，time可取值为1,5,15,30,60，1表示分时，5，15,30,60分别表示对应的分钟k




产品代码参考值：
国际期货：
美原油：CL , 美黄金：GC, 美白银：SI, 美铜：CAD, 德指：恒指：小恒指：富时：小纳指：NAS，英镑：BP，欧元：EC，澳元：加元：CD

国内期货：
沪金：AU0, 沪银：AG0, 沪铜：CU0, 沪镍：NI0，沥青：BU0，天然橡胶：螺纹钢：RB0，棕榈油：P0，白糖：SR0，豆粕：M0，豆油：Y0，PP聚丙烯：V0
