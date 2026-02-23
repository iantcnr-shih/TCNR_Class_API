<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    public function generate()
    {
        return response()->json([
            'url' => captcha_src()
        ]);
    }
}
