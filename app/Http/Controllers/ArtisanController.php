<?php

namespace App\Http\Controllers;

use App\Models\Artisan;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Recherche et détail des artisans.
 *
 * Reprend les filtres de `myReqArtisant()` et la jointure
 * `artisant` ⋈ `metier` ⋈ `categoriemetier`, avec des requêtes
 * paramétrées.
 */
class ArtisanController extends Controller
{
    private const COLUMNS = [
        'artisant.IDARTISANT',
        'artisant.Nom',
        'artisant.Prenom',
        'artisant.Ville',
        'artisant.Commune',
        'artisant.Quartier',
        'artisant.Contact',
        'artisant.NumWhatApp',
        'artisant.Description',
        'artisant.Photo',
        'artisant.DateInscription',
        'artisant.Tnote',
        'artisant.Tvote',
        'artisant.Recommende',
        'artisant.IDmetier',
        'artisant.IDcategoriemetier',
        'metier.Libelle',
        'categoriemetier.Categorie',
    ];

    public function index(Request $request)
    {
        $pagination = $this->pagination($request);

        $query = $this->baseQuery();
        $this->applyFilters($query, $request);

        $total = (clone $query)->count();

        // Le seed doit rester stable entre les appels d'une même recherche
        // paginée : sans lui, RAND() est réévalué à chaque requête et les
        // pages successives (offset croissant) se chevauchent ou sautent
        // des résultats.
        $data = $query->select(self::COLUMNS)
            ->inRandomOrder($this->randomSeed($request))
            ->offset($pagination['offset'])
            ->limit($pagination['limit'])
            ->get();

        return $this->paginatedResponse($data, $total, $pagination);
    }

    public function show(int $id)
    {
        $artisan = $this->baseQuery()
            ->select(self::COLUMNS)
            ->where('artisant.IDARTISANT', $id)
            ->first();

        $this->abortIf(! $artisan, 404, 'Artisan introuvable.');

        return response()->json($artisan);
    }

    public function notations(int $id)
    {
        $this->abortUnless(Artisan::whereKey($id)->exists(), 404, 'Artisan introuvable.');

        $notations = DB::table('notation')
            ->where('IDARTISANT', $id)
            ->orderByDesc('dateNote')
            ->get(['IDNOTATION', 'TitreCommentaire', 'NomUtilisateur', 'Note', 'Commentaire', 'dateNote']);

        return response()->json(['data' => $notations]);
    }

    public function storeNotation(Request $request, int $id)
    {
        $this->abortUnless(Artisan::whereKey($id)->exists(), 404, 'Artisan introuvable.');

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
            'IDARTISANT' => $id,
            'IDCOMPAGNIE' => 0,
        ], 'IDNOTATION');

        return response()->json(['id' => $notationId], 201);
    }

    private function baseQuery(): Builder
    {
        return DB::table('artisant')
            ->join('metier', 'metier.IDmetier', '=', 'artisant.IDmetier')
            ->join('categoriemetier', 'categoriemetier.IDcategoriemetier', '=', 'artisant.IDcategoriemetier');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query
            ->when(
                $request->filled('metier'),
                fn (Builder $q) => $q->where('metier.Libelle', 'like', '%'.$request->query('metier').'%')
            )
            ->when(
                $request->filled('categorie'),
                fn (Builder $q) => $q->where('categoriemetier.Categorie', $request->query('categorie'))
            )
            ->when(
                $request->filled('ville'),
                fn (Builder $q) => $q->where('artisant.Ville', $request->query('ville'))
            )
            ->when(
                $request->filled('commune'),
                fn (Builder $q) => $q->where('artisant.Commune', 'like', '%'.$request->query('commune').'%')
            )
            ->when(
                $request->filled('quartier'),
                fn (Builder $q) => $q->where('artisant.Quartier', 'like', '%'.$request->query('quartier').'%')
            )
            ->when(
                $request->filled('note_min'),
                fn (Builder $q) => $q->where('artisant.Tnote', '>=', (float) $request->query('note_min'))
            );
    }
}
