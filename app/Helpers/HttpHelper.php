<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Type\ResponseType;

/**
 * Class HelperHttp faz requisicoes http e é visivel em toda a minha aplicacao.
 * Retorna uma instancia da classe ResponseType
 * podendo ser encontrada em App\Type\ResponseType;
*/
class HttpHelper {


    /**
     * Faz um request do tipo GET podendo ser autenticado ou nao
     */
    private static function GetRequest(string $url, ?string $token): Response
    {
        if(is_null($token)) return Http::get($url);

        return Http::withToken($token)->get($url);
    }


    /**
     * Faz um request to tipo POST  podendo ser autenticado ou nao
     */
    private static function PostRequest(string $url, ?string $token, ?array $data): Response
    {

       $data = (is_array($data) ? $data : []);

        if(is_null($token)) return Http::post($url, $data);

        return Http::withToken($token)->post($url, $data);
    }


    /**
     * SendRequest vai estruturar o request e verificar se ele e um GET ou POST
     */
    private static function SendRequest(string $type, string $url, ?string $token, ?array $data): ResponseType
    {
        if(strtoupper($type) == "GET") {
            return new ResponseType(self::GetRequest($url, $token));
        } else {
            return new ResponseType(self::PostRequest($url, $token, $data));
        }
    }


    /**
     *  Realiza uma requisicao http simples
     */
    public static function FastHttpRequest(string $type, string $url,?string $token=null, ?array $data=null ): ResponseType
    {
        return self::SendRequest($type, $url, $token, $data);
    }
}
