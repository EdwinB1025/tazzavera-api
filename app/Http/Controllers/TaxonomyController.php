<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaxonomyResource;
use App\Models\OlfactoryTaxonomy;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    public function index()
    {
        $tree = OlfactoryTaxonomy::whereNull('parent_id')   // solo raíces
            ->with('children.children')                       // carga 3 niveles
            ->get();

        return TaxonomyResource::collection($tree);
    }
}
