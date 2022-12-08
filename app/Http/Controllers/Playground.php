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
use Illuminate\Support\Facades\Cache;


class Playground extends Controller
{
    public function debugData(Request $request, $apiToken) {
        $token_data = $this->getTokenData($apiToken);
        if (!$token_data) return view('welcome');

        if ($request->query('focus')) {
            $focus_id = 'focus_' . $request->query('focus') . '_' . $token_data->id;
            $debug_data = Cache::store('file')->remember($focus_id, 1, function() use ($token_data, $request) {
                return DB::table('incoming_data')
                    ->where('token_id', $token_data->id)
                    ->where('id', (int)$request->query('focus'))
                    ->first();
            });
        } else {
            $debug_data = Cache::store('file')->remember('focus_last_' . $token_data->id, 1, function() use ($token_data) {
                return DB::table('incoming_data')
                    ->where('token_id', $token_data->id)
                    ->orderBy('id', 'desc')
                    ->first();
            });
        }

        $index_data = Cache::store('file')->remember('index_data_' . $token_data->id, 1, function() use ($token_data) {
           return DB::table('incoming_data')
               ->select('id', 'fwd_status', 'created_at')
               ->where('token_id', $token_data->id)
               ->orderBy('id', 'desc')
               ->get();
        });

        return view('debug', [
            'title' => $token_data->token,
            'fwd_url' => $token_data->fwd_url,
            'debug_data' => $debug_data,
            'index_data' => $index_data
        ]);
    }

    public function entryPoint(Request $request, $apiToken) {
        $method = $request->method();

        if ($method == 'GET') {
            return redirect("/debug/{$apiToken}");
        }

        $originData = [
            'apiToken' => $apiToken,
            'method' => $method,
            'header' => $request->header(),
            'payload' => $request->all()
        ];

        $token_data = $this->getTokenData($apiToken);
        if (!$token_data) return ['success' => false];

        $headers = [];
        $is_json = false;
        foreach($originData['header'] as $key => $val) {
            if (in_array($key, ['cache-control', 'content-type'])) {
                $headers[] = "{$key}: " . implode(',', $val);
                if ($key == 'content-type') {
                    $is_json = 'application/json' == $val[0];
                }
            }
        }

        $readyToFwd = [
            'fwd_url' => $token_data->fwd_url,
            'method' => $originData['method'],
            'header' => $headers,
            'payload' => $is_json ? json_encode($originData['payload']) : http_build_query($originData['payload'])
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
            DB::table('incoming_data')
                ->where('id', '<',$deleteExceedData->id)
                ->where('token_id', $token_data->id)
                ->delete();
        }

        return [
            'fwd_response' => $resp,
            'field_data' => $originData,
        ];
    }

    private function getTokenData($apiToken) {
        return Cache::store('file')->remember('getToken_' . $apiToken, 10, function() use ($apiToken) {
            return DB::table('token_data')->where('token', $apiToken)->first();
        });
    }

    private function forwardRequest($originData) {
        if (!$originData['fwd_url']) {
            return [
                'success' => true,
                'message' => '___DEBUG___'
            ];
        }

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
