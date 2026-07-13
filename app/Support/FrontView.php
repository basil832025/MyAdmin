<?php

namespace App\Support;

use Illuminate\Support\Facades\View;
use App\View\Composers\HeaderPhonesComposer;

class FrontView
{
    public static function register(): void
    {
        // вывод телефонов в хидере или в других местах
        View::composer(['layouts.*', 'partials.*', 'pages.*', 'components.*', 'front.*.layouts.*', 'front.*.partials.*', 'front.*.pages.*', 'front.*.components.*'], HeaderPhonesComposer::class);
    }
}
