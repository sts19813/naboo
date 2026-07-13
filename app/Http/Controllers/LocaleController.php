<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(string $lang)
    {
        session(['locale' => $lang]);
        return back();
    }
}
