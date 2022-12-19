<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

/**
 *
 */
class AbfrageExport implements FromView
{
    /**
     * @var
     */
    public $rueckmeldung;

    /**
     * @param $rueckmeldung
     */
    public function __construct($rueckmeldung)
    {
        $this->rueckmeldung = $rueckmeldung;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        $this->rueckmeldung->load('userRueckmeldungen');

        return view('export.abfrageExport', [
            'rueckmeldung' => $this->rueckmeldung,
        ]);
    }
}
