<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;

class BaseResponse implements Responsable
{
    /**
     * request status code will be in this variable
     * @var int
     */
    protected int $httpCode;

    /**
     * request status code will be in this variable
     * @var int
     */
    protected int $statusCode;

    /**
     * response message will be in this variable
     * @var mixed
     */
    public $message;

    /**
     * model data will be in this variable
     * @var mixed
     */
    protected $data;

    /**
     * view path will be in this variable
     * @var string|null
     */
    protected $view;

    /**
     * token in this variable
     * @var string|null
     */
    protected $token;
    protected $pagination;
    protected string $paginationKey = 'pagination';
    protected $emptyArray;

    /**
     * Default constructor to load data and view
     * @param int $code
     * @param mixed $message
     * @param mixed $data
     * @param string|null $view
     * @param string $paginationKey JSON key for pagination payload (e.g. pagination or meta)
     */
    public function __construct(int $httpCode, int $statusCode, string $message, mixed $data = [], $token = null, $view = null, $pagination = null, $emptyArray = false, string $paginationKey = 'pagination')
    {
        $this->httpCode    = $httpCode;
        $this->statusCode    = $statusCode;
        $this->message = $message;
        $this->data    = $data;
        $this->token    = $token;
        $this->view    = $view;
        $this->pagination    = $pagination;
        $this->paginationKey = $paginationKey;
        $this->emptyArray    = $emptyArray;
    }

    /**
     * Responsible method to return either view or JSON as per request
     * @param  object $request
     */
    public function toResponse($request)
    {
        if ($this->view) {
            return response()->view($this->view, [
                'data' => $this->data,
            ]);
        }

        $jsonData = [
            'status' => $this->statusCode,
            'message' => $this->message,
        ];

        if ($this->emptyArray && empty($this->data)) {
            $jsonData['data'] = [];
        }

        if ($this->data) {
            $jsonData['data'] = $this->data;
        }

        if ($this->pagination) {
            $jsonData[$this->paginationKey] = $this->pagination;
        }

        if ($this->token) {
            $jsonData['token'] = $this->token;
        }

        return response()->json(
            $jsonData,
            $this->httpCode
        );
    }
}
