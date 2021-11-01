<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\HttpHelper;
use App\Helpers\FlightHelper;

class FlightController extends Controller
{



    public function getFlightApi(Request $request): JsonResponse
    {
        $response = HttpHelper::FastHttpRequest("GET", "http://prova.123milhas.net/api/flights")->getResponse()->response->json();

        $data = (new FlightHelper($response))->getData();

        return response()->json($data, 200);
    }
}
