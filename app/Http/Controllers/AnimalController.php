<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Illuminate\View\View;

class AnimalController extends Controller
{
    public function index(): View
    {
        return view('animaux.index', [
            'animaux' => Animal::query()
                ->withCount('evenementsSanitaires')
                ->orderBy('identifiant')
                ->paginate(20),
        ]);
    }
}
