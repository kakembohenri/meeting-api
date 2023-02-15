<?php

namespace App\CustomHelper;

class Result
{

    protected $data;

    protected $msg;

    protected $token;

    protected int $statusCode;

    public function __construct($data, $msg, $token, $statusCode)
    {
        $this->data = $data;

        $this->msg = $msg;

        $this->token = $token;

        $this->statusCode = $statusCode;
    }


    public static function Simple($msg, $statusCode)
    {
        $result = [
            "msg" => $msg,
            "StatusCode" => $statusCode,
        ];

        return $result;
    }

    public static function WithResult($data, $statusCode)
    {
        $result = [
            "result" => $data,
            "StatusCode" => $statusCode,
        ];

        return $result;
    }

    public static function Meetings($meeting, $attendees)
    {
        $result[] = [
            "meeting" => $meeting,
            "attendees" => $attendees,
            "StatusCode" => 200
        ];

        return $result;
    }

    public static function Error($msg, $StatusCode)
    {
        $result = [
            "msg" => $msg,
            "StatusCode" => $StatusCode
        ];

        return $result;
    }

    public static function SignIn($msg,  $user, $token)
    {
        $result = [
            "msg" => $msg,
            "user" => $user,
            "StatusCode" => 200,
            "token" => $token
        ];

        return $result;
    }
}
