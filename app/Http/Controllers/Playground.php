<?php
/**
 * Created by IntelliJ IDEA.
 * User: kiattirat
 * Date: 9/7/2019 AD
 * Time: 17:24
 */

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class Playground extends Controller
{
    public function debugData(Request $request, $apiToken) {
        $token_data = DB::table('token_data')->where('token', $apiToken)->first();
        if (!$token_data) return view('welcome');

        if ($request->query('focus')) {
            $debug_data = DB::table('incoming_data')
                ->where('token_id', $token_data->id)
                ->where('id', (int) $request->query('focus'))
                ->first();
        } else {
            $debug_data = DB::table('incoming_data')
                ->where('token_id', $token_data->id)
                ->orderBy('id', 'desc')
                ->first();
        }

        $index_data = DB::table('incoming_data')
            ->select('id', 'fwd_status', 'created_at')
            ->where('token_id', $token_data->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('debug', [
            'title' => $token_data->token,
            'fwd_url' => $token_data->fwd_url,
            'debug_data' => $debug_data,
            'index_data' => $index_data
        ]);
    }

    public function entryPoint(Request $request, $apiToken) {
        $method = $request->method();
        $originData = [
            'apiToken' => $apiToken,
            'method' => $method,
            'header' => $request->header(),
            'payload' => $request->all()
        ];

        $token_data = DB::table('token_data')->where('token', $apiToken)->first();
        if (!$token_data) return ['success' => false];

        $headers = [];
        foreach($originData['header'] as $key => $val) {
            if (in_array($key, ['cache-control', 'content-type'])) {
                $headers[] = "{$key}: " . implode(',', $val);
            }
        }

        $readyToFwd = [
            'fwd_url' => $token_data->fwd_url,
            'method' => $originData['method'],
            'header' => $headers,
            'payload' => http_build_query($originData['payload'])
        ];

        $resp = $this->forwardRequest($readyToFwd);

        DB::table('incoming_data')->insert([
            'token_id' => $token_data->id,
            'method' => $method,
            'header' => json_encode($originData['header']),
            'payload' => json_encode($originData['payload']),
            'fwd_status' => $resp['success'],
            'fwd_response' => $resp['message'],
        ]);

        // Clear Exceed data
        $deleteExceedData = DB::table('incoming_data')->select('id')->where('token_id', $token_data->id)->offset(101)->orderBy('id', 'desc')->first();
        if ($deleteExceedData) {
            DB::table('incoming_data')->where('id', $deleteExceedData->id)->delete();
        }

        return [
            'fwd_response' => $resp,
            'field_data' => $originData,
        ];
    }

    private function forwardRequest($originData) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $originData['fwd_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $originData['method'],
            CURLOPT_POSTFIELDS => $originData['payload'],
            CURLOPT_HTTPHEADER => $originData['header']
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if (!$err) return ['success' => true, 'message' => $response];

        return [
            'success' => false,
            'message' => $err
        ];
    }
}
