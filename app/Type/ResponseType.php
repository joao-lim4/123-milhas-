<?php

namespace App\Type;

use Illuminate\Http\Client\Response;

class ResponseType{

    /** @var Response */
    private $response;

    /** @var StatusCode */
    private $status_code;

    /** @var HttpOk */
    private $HttpOk = false;

    private $messageDefault = [
        "Request feito com sucesso",
        "Algo de errado aconteceu, revise a sua requisição!"
    ];

    private $message;


    public function __construct( ?Response $response)
    {

        if($response instanceof Response){
            $this->response = $response;
            $this->status_code = $response->status();

            if($response->ok()) $this->HttpOk = true;

            $this->message = ($response->status() < 300 ? $this->messageDefault[0] : $this->messageDefault[1]);

        }else{
            $this->response = null;
            $this->status_code = 500;
        }

    }

    /**
     * Considerei o estatus < 300 pq se for de 299 para baixo deu bom em teoria kk
     * 300 para cima algo de errado não ta certo verifica o request ae
     */
    public function getResponse()
    {
        return (object) [
            "response" => $this->response,
            "status" => $this->status_code,
            "message" => $this->status_code < 300 ? $this->messageDefault[0] : $this->messageDefault[1]
        ];
    }


    public function getMessage() { return $this->message; }

    public function getStatusCode() { return $this->status_code; }

    public function getHttpOk() { return $this->HttpOk; }
}
