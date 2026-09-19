<?php

namespace App\Http\Controllers;

use App\Models\Ville;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VilleController extends Controller
{
    private const COLUMNS = [
        'ville.IDville',
        'ville.NomVille',
    ];

    public function index(Request $request)
    {
        $pagination = $this->pagination($request);

        $query = $this->baseQuery();

        $total = (clone $query)->count();

        $data = $query->select(self::COLUMNS)
            ->offset($pagination['offset'])
            ->limit($pagination['limit'])
            ->orderBy('NomVille')
            ->get();

        return $this->paginatedResponse($data, $total, $pagination);
    }

    public function show(int $id)
    {
        $ville = $this->baseQuery()
            ->select(self::COLUMNS)
            ->where('ville.IDville', $id)
            ->first();

        $this->abortIf(! $ville, 404, 'Ville introuvable.');

        return response()->json($ville);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'NomVille' => 'required|string|max:255',
        ]);

        $validated = $request->only(['NomVille']);

        $villeId = DB::table('ville')->insertGetId($validated, 'IDville');

        $ville = DB::table('ville')
            ->select(self::COLUMNS)
            ->where('IDville', $villeId)
            ->first();

        return response()->json($ville, 201);
    }

    public function update(Request $request, int $id)
    {
        $this->abortUnless(Ville::whereKey($id)->exists(), 404, 'Ville introuvable.');

        $this->validate($request, [
            'NomVille' => 'sometimes|required|string|max:255',
        ]);

        $validated = $request->only(['NomVille']);

        DB::table('ville')->where('IDville', $id)->update($validated);

        $ville = DB::table('ville')
            ->select(self::COLUMNS)
            ->where('IDville', $id)
            ->first();

        return response()->json($ville);
    }

    public function destroy(int $id)
    {
        $this->abortUnless(Ville::whereKey($id)->exists(), 404, 'Ville introuvable.');

        DB::table('ville')->where('IDville', $id)->delete();

        return response()->json(null, 204);
    }

    private function baseQuery(): Builder
    {
        return DB::table('ville');
    }
}