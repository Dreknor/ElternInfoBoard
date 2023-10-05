<?php

namespace App\Http\Controllers;

use App\Model\Post;
use App\Model\ReadReceipts;
use Illuminate\Http\Request;

class ReadReceiptsController extends Controller
{

    public function store(Request $request)
    {
        ReadReceipts::firstOrCreate([
            'post_id' => $request->post_id,
            'user_id' => auth()->id(),
        ]);
        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Lesebestätigung erfolgreich gespeichert.',
        ]);
    }
}
