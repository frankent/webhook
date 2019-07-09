<?php
/**
 * Created by IntelliJ IDEA.
 * User: kiattirat
 * Date: 9/7/2019 AD
 * Time: 17:24
 */

namespace App\Http\Controllers;
use Illuminate\Http\Request;


class Playground extends Controller
{
    public function entryPoint(Request $request, $apiToken) {
        $method = $request->method();
        $originData = [
            'apiToken' => $apiToken,
            'method' => $method,
            'header' => $request->header(),
            'payload' => $request->all()
        ];

        $headers = [];
        foreach($originData['header'] as $key => $val) {
            $headers[] = "{$key}: " . implode(',', $val);
        }

        $readyToFwd = [
            'method' => $originData['method'],
            'header' => $headers,
            'payload' => http_build_query($originData['payload'])
        ];

        $resp = $this->forwardRequest($readyToFwd);

        return [
            'fwd_response' => $resp,
            'field_data' => $originData,
        ];
    }

    private function forwardRequest($originData) {
        $url = 'https://engy95ta8b0vq.x.pipedream.net';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $originData['method'],
            CURLOPT_POSTFIELDS => $originData['payload'],
            CURLOPT_HTTPHEADER => $originData['header']
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if (!$err) return $response;

        return "cURL Error #:" . $err;
    }
}
