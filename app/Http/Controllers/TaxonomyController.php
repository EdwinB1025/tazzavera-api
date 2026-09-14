<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaxonomyResource;
use App\Models\OlfactoryTaxonomy;
use Illuminate\Http\Request;

class TaxonomyController extends Controller
{
    public function index()
    {
        $TaxonomyTree = OlfactoryTaxonomy::whereNull('parent_id')   // EDB 09/14/31: Loaded with parent level 0
            ->with('children.children')                       // EDB 09/14/31: Loaded with 1st and 2nd children level
            ->get();

        return TaxonomyResource::collection($TaxonomyTree);
    }
}
