<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Support\Facades\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 *
 */
class TerminController extends Controller
{

 public function __construct()
 {
        //$this->middleware('auth:sanctum');
 }

 public function index()
 {
        $user = request()->user();
        
        $termine = $user->termine()->where('start', '>=', now()->startOfDay())->get();
        $termine = $termine->unique('id');
        $termine = $termine->map(function ($termin) {
            $termin->start = $termin->start->format('Y-m-d H:i:s');
            $termin->ende = $termin->ende->format('Y-m-d H:i:s');
            return $termin;
        });
        return response()->json($termine);
 }

}
