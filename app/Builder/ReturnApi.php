<?php

namespace App\Builder;

class ReturnApi
{
    public static function success($data = null, string $message = "", int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            "error"   => false,
            "message" => $message,
            "data"    => $data,
        ], $status);
    }

    public static function error(string $message = "", $data = null, int $status = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            "error"   => true,
            "message" => $message,
            "data"    => $data,
        ], $status);
    }
}
