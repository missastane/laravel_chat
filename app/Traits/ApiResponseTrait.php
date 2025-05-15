<?php

namespace App\Traits;

trait ApiResponseTrait
{
    public function success($data = null, $message = null, $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function error($message = 'خطایی غیرمنتظره در سرور رخ داده است. لطفا دوباره تلاش کنید', $code = 500)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $code);
    }
}
