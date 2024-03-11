<?php

namespace App\Http\Controllers;

use App\Models\Term;
use Illuminate\Http\Request;

class TermController extends Controller
{
    //

    public function getTerms(Request $request)
    {
        
        $type = $request->query('type');

        $terms = Term::where('type', $type)->latest()->first();
        
        return response()->json($terms);
    }
}
