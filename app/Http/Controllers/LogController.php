<?php

namespace App\Http\Controllers;

use danielme85\LaravelLogToDB\LogToDB;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(){
        return view('logs.index',[
            'logs' => LogToDB::model()->orderBy('created_at', 'desc')->paginate(30)

        ]);
    }
}
