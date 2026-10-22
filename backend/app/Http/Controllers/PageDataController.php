<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageDataController extends Controller
{
    public function services()
    {
        $services = [
            [
                'id' => 1,
                'title' => 'Choose a trading Plan',
                'description' => 'Lorem Ipsum is simply dummy text of the printing',
                'delay' => '.4s',
            ],
            [
                'id' => 2,
                'title' => 'Machine Learning',
                'description' => 'Lorem Ipsum is simply dummy text of the printing',
                'delay' => '.6s',
            ],
            [
                'id' => 3,
                'title' => 'Data Visualization',
                'description' => 'Lorem Ipsum is simply dummy text of the printing',
                'delay' => '.7s',
            ],
        ];

        return response()->json($services);
    }
}
