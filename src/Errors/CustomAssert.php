<?php


namespace App\Errors;


class CustomAssert implements \JsonSerializable
{
    private string $property_path;

    private string $message;

    /**
     * CustomAssert constructor.
     * @param string $property_path
     * @param string $message
     */
    public function __construct(string $property_path, string $message)
    {
        $this->property_path = $property_path;
        $this->message = $message;
    }

    public function jsonSerialize() : array
    {
        return [
            'property_path' => $this->property_path,
            'message' => $this->message
        ];
    }


}