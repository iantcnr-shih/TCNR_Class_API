<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public function sendTest()
    {
        Mail::raw('這是一封自動發送測試信', function ($message) {
            $message->to('ian.service.tcnr@gmail.com')
                    ->subject('TCNR 自動通知');
        });

        return response()->json([
            'message' => '寄信完成'
        ]);
    }
}
