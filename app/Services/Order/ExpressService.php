<?php

namespace App\Services\Order;

use App\Services\BaseService;

class ExpressService extends BaseService
{
    public function getExpressName($code)
    {
        return [
                "ZTO" => "中通快递",
                "YTO" => "圆通速递",
                "YD" => "韵达速递",
                "YZPY" => "邮政快递包裹",
                "EMS" => "EMS",
                "DBL" => "德邦快递",
                "FAST" => "快捷快递",
                "ZJS" => "宅急送",
                "TNT" => "TNT快递",
                "UPS" => "UPS",
                "DHL" => "DHL",
                "FEDEX" => "FEDEX联邦(国内件)",
                "FEDEX_GJ" => "FEDEX联邦(国际件)",
            ][$code] ?? '';
    }

    public function getOrderTraces($com, $code)
    {
        $requestData = "{'OrderCode':'','ShipperCode':'$com','LogisticCode':'$code'}";
        $reqUrl = env('EXPRESS_URL');
        $appKey = env('EXPRESS_APP_KEY');
        $businessId = env('EXPRESS_APP_ID');

        $datas = array(
            'EBusinessID' => $businessId,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $appKey);

        $res = $this->sendPost($reqUrl, $datas);
        $res = json_decode($res, true);
        return $res;
    }

    private function sendPost($url, $datas)
    {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info  = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd         = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets       = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    private function encrypt($data, $appkey)
    {
        return urlencode(base64_encode(md5($data . $appkey)));
    }
}
