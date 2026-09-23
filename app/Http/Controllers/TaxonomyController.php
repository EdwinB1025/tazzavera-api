<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaxonomyResource;
use App\Models\OlfactoryTaxonomy;
use Illuminate\Http\Request;

/**
 * List olfactory taxonomies
 *
 * Returns the full olfactory taxonomy tree used to classify coffee aromas and
 * flavors. The tree is returned as a nested structure: top-level categories
 * (parent_id = null) with their children and grandchildren eager-loaded
 * (two levels deep).
 *
 * @group Taxonomies
 *
 * @unauthenticated
 * 
 * @responseFile storage/scribe/responses/taxonomies.index.json
 * 
 */
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
