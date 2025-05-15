<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedConversationActionException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'status' => false,
            'message' => $this->getMessage() ?: 'شما مجوز انجام اون عملیات را ندارید',
        ], 403);
    }
}
