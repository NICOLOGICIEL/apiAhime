<?php

namespace App\Http\Controllers;

use App\Models\Compagnie;
use App\Models\Depart;
use App\Models\LigneTransport;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Recherche des lignes de transport, horaires de départ et escales.
 *
 * Reprend `myReqTransport()`, `myReqHoraire()` et `myReqEscale()`
 * (lib/config/my_config.dart) avec des requêtes paramétrées.
 */
class TransportController extends Controller
{
    private const COLUMNS = [
        'lignetransport.IDLIGNETRANSPORT',
        'lignetransport.VilleDepart',
        'lignetransport.VilleArrivee',
        'lignetransport.CodeLigne',
        'compagnie.IDCOMPAGNIE',
        'compagnie.Nom',
        'compagnie.Ville',
        'compagnie.Commune',
        'compagnie.Quartier',
        'compagnie.Adresse',
        'compagnie.Contact',
        'compagnie.NumWhatApp',
        'compagnie.Photo',
        'compagnie.LongitudeComp',
        'compagnie.LatitudeComp',
    ];

    public function index(Request $request)
    {
        $pagination = $this->pagination($request);

        $query = DB::table('lignetransport')
            ->join('compagnie', 'compagnie.IDCOMPAGNIE', '=', 'lignetransport.IDCOMPAGNIE');

        $query
            ->when(
                $request->filled('ville_depart'),
                fn (Builder $q) => $q->where('lignetransport.VilleDepart', $request->query('ville_depart'))
            )
            ->when(
                $request->filled('ville_arrivee'),
                fn (Builder $q) => $q->where('lignetransport.VilleArrivee', $request->query('ville_arrivee'))
            )
            ->when(
                $request->filled('compagnie'),
                fn (Builder $q) => $q->where('compagnie.Nom', 'like', '%'.$request->query('compagnie').'%')
            );

        $total = (clone $query)->count();

        $data = $query->select(self::COLUMNS)
            ->offset($pagination['offset'])
            ->limit($pagination['limit'])
            ->get();

        return $this->paginatedResponse($data, $total, $pagination);
    }

    public function horaires(int $ligneId)
    {
        $this->abortUnless(LigneTransport::whereKey($ligneId)->exists(), 404, 'Ligne de transport introuvable.');

        $horaires = DB::table('depart')
            ->join('commoditetransport', 'depart.IDDEPART', '=', 'commoditetransport.IDDEPART')
            ->where('depart.IDLIGNETRANSPORT', $ligneId)
            ->orderBy('depart.HeureDepart')
            ->get([
                'depart.IDDEPART',
                'depart.HeureDepart',
                'depart.Prix',
                'depart.NumDepart',
                'commoditetransport.Wifi',
                'commoditetransport.Climatiseur',
                'commoditetransport.Toilette',
                'commoditetransport.petitDej',
            ]);

        return response()->json(['data' => $horaires]);
    }

    public function escales(int $departId)
    {
        $this->abortUnless(Depart::whereKey($departId)->exists(), 404, 'Départ introuvable.');

        $escales = DB::table('escale')
            ->where('IDDEPART', $departId)
            ->get(['IDESCALE', 'Localite', 'LongitudeEsc', 'LatitudeEsc']);

        return response()->json(['data' => $escales]);
    }

    public function compagnieNotations(int $compagnieId)
    {
        $this->abortUnless(Compagnie::whereKey($compagnieId)->exists(), 404, 'Compagnie introuvable.');

        $notations = DB::table('notation')
            ->where('IDCOMPAGNIE', $compagnieId)
            ->orderByDesc('dateNote')
            ->get(['IDNOTATION', 'TitreCommentaire', 'NomUtilisateur', 'Note', 'Commentaire', 'dateNote']);

        return response()->json(['data' => $notations]);
    }

    public function storeCompagnieNotation(Request $request, int $compagnieId)
    {
        $this->abortUnless(Compagnie::whereKey($compagnieId)->exists(), 404, 'Compagnie introuvable.');

        $this->validate($request, [
            'nom_utilisateur' => 'required|string|max:50',
            'titre' => 'required|string|max:255',
            'note' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string',
        ]);
        $validated = $request->only(['nom_utilisateur', 'titre', 'note', 'commentaire']);

        $notationId = DB::table('notation')->insertGetId([
            'TitreCommentaire' => $validated['titre'],
            'NomUtilisateur' => $validated['nom_utilisateur'],
            'Note' => $validated['note'],
            'Commentaire' => $validated['commentaire'] ?? null,
            'dateNote' => date('Y-m-d H:i:s'),
            'IDCOMPAGNIE' => $compagnieId,
            'IDARTISANT' => 0,
        ], 'IDNOTATION');

        return response()->json(['id' => $notationId], 201);
    }
}
