<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

/**
 * Listes de référence utilisées pour peupler les menus déroulants de
 * recherche (villes, métiers, catégories, compagnies) — remplace les
 * appels `ReqMultiExec` de `getdataAll()`/`getdataVille()`.
 */
class ReferenceController extends Controller
{
    public function villes()
    {
        return response()->json([
            'data' => DB::table('ville')->orderBy('NomVille')->pluck('NomVille'),
        ]);
    }

    public function metiers()
    {
        return response()->json([
            'data' => DB::table('metier')->orderBy('Libelle')->pluck('Libelle'),
        ]);
    }

    public function categories()
    {
        return response()->json([
            'data' => DB::table('categoriemetier')->orderBy('Categorie')->pluck('Categorie'),
        ]);
    }

    public function compagnies()
    {
        return response()->json([
            'data' => DB::table('compagnie')->orderBy('Nom')->get(['IDCOMPAGNIE', 'Nom']),
        ]);
    }
}
