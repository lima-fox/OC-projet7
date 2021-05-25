<?php


namespace App\Errors;


class Error implements \JsonSerializable
{
    private string $message;

    private int $status;

    /**
     * Error constructor.
     * @param string $message
     * @param int $status
     */
    public function __construct(string $message, int $status)
    {
        $this->message = $message;
        $this->status = $status;
    }


    public function jsonSerialize() : array
    {
        return [
            'status' => $this->status,
            'message' => $this->message
        ];

    }


}