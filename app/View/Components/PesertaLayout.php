<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PesertaLayout extends Component
{
    public function render(): View
    {
        return view('layouts.peserta');
    }
}
