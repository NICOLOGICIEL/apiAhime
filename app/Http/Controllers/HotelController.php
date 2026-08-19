<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Recherche et détail des hôtels.
 *
 * Reprend les filtres de `myReq()` (lib/config/my_config.dart) et la
 * jointure `hotel` ⋈ `commoditehotel` de `api_service.dart`, mais avec des
 * requêtes paramétrées (aucun SQL n'est jamais construit à partir d'une
 * chaîne envoyée par le client).
 */
class HotelController extends Controller
{
    private const COLUMNS = [
        'hotel.IDHOTEL',
        'hotel.NomEtab',
        'hotel.Ville',
        'hotel.Commune',
        'hotel.Quartier',
        'hotel.Adresse',
        'hotel.PrixMini',
        'hotel.Description',
        'hotel.Contact',
        'hotel.NumWhatApp',
        'hotel.Situation',
        'hotel.Longitude',
        'hotel.Latitude',
        'hotel.Image',
        'hotel.Recommende',
        'commoditehotel.NbrEtoile',
        'commoditehotel.Wifi',
        'commoditehotel.Piscine',
        'commoditehotel.Spa',
        'commoditehotel.Bar',
        'commoditehotel.Ventilateur',
        'commoditehotel.Climatiseur',
        'commoditehotel.EstResidence',
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
        $hotel = $this->baseQuery()
            ->select(self::COLUMNS)
            ->where('hotel.IDHOTEL', $id)
            ->first();

        $this->abortIf(! $hotel, 404, 'Hôtel introuvable.');

        return response()->json($hotel);
    }

    public function images(int $id)
    {
        $this->abortUnless(Hotel::whereKey($id)->exists(), 404, 'Hôtel introuvable.');

        $images = DB::table('imagehotel')
            ->where('IDHOTEL', $id)
            ->orderBy('NumPhoto')
            ->get(['IDIMAGE', 'Libelle', 'Description', 'Photo', 'NumPhoto']);

        return response()->json(['data' => $images]);
    }

    private function baseQuery(): Builder
    {
        return DB::table('hotel')
            ->join('commoditehotel', 'hotel.IDHOTEL', '=', 'commoditehotel.IDHOTEL');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query
            ->when(
                $request->filled('nom_etab'),
                fn (Builder $q) => $q->where('hotel.NomEtab', 'like', '%'.$request->query('nom_etab').'%')
            )
            ->when(
                $request->filled('ville'),
                fn (Builder $q) => $q->where('hotel.Ville', $request->query('ville'))
            )
            ->when(
                $request->filled('commune'),
                fn (Builder $q) => $q->where('hotel.Commune', $request->query('commune'))
            )
            ->when(
                $request->filled('quartier'),
                fn (Builder $q) => $q->where('hotel.Quartier', 'like', '%'.$request->query('quartier').'%')
            )
            ->when(
                $request->filled('prix_max'),
                fn (Builder $q) => $q->where('hotel.PrixMini', '<=', (int) $request->query('prix_max'))
            )
            ->when(
                $request->filled('nbr_etoile'),
                fn (Builder $q) => $q->where('commoditehotel.NbrEtoile', (int) $request->query('nbr_etoile'))
            )
            ->when($request->boolean('wifi'), fn (Builder $q) => $q->where('commoditehotel.Wifi', 1))
            ->when($request->boolean('piscine'), fn (Builder $q) => $q->where('commoditehotel.Piscine', 1))
            ->when($request->boolean('spa'), fn (Builder $q) => $q->where('commoditehotel.Spa', 1))
            ->when($request->boolean('bar'), fn (Builder $q) => $q->where('commoditehotel.Bar', 1))
            ->when($request->boolean('ventilateur'), fn (Builder $q) => $q->where('commoditehotel.Ventilateur', 1))
            ->when($request->boolean('climatiseur'), fn (Builder $q) => $q->where('commoditehotel.Climatiseur', 1));

        // Comme côté client (xEstResidence/xEstHotel) : un filtre n'est
        // appliqué que si exactement l'un des deux drapeaux est actif.
        $estResidence = $request->boolean('est_residence');
        $estHotel = $request->boolean('est_hotel');

        if ($estResidence xor $estHotel) {
            $query->where('commoditehotel.EstResidence', $estResidence ? 1 : 0);
        }
    }
}
