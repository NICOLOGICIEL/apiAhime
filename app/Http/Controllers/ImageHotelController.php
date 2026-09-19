<?php

namespace App\Http\Controllers;

use App\Models\ImageHotel;
//use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImageHotelController extends Controller
{
    private const COLUMNS = [
        'IDIMAGE',
        'IDHOTEL',
        'Libelle',
        'Description',
        'Photo',
        'NumPhoto',
    ];

    public function index(Request $request)
    {
        $pagination = $this->pagination($request);

        $query = DB::table('imagehotel');

        $total = $query->count();

        $data = $query->select(self::COLUMNS)
            ->offset($pagination['offset'])
            ->limit($pagination['limit'])
            ->orderByDesc('IDIMAGE')
            ->get();

        return $this->paginatedResponse($data, $total, $pagination);
    }

    public function show(int $id)
    {
        $image = DB::table('imagehotel')
            ->select(self::COLUMNS)
            ->where('IDIMAGE', $id)
            ->first();

        $this->abortIf(! $image, 404, 'Image introuvable.');

        return response()->json($image);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'IDHOTEL' => 'required|integer|exists:hotel,IDHOTEL',
            'Libelle' => 'nullable|string|max:255',
            'Description' => 'nullable|string|max:1000',
            'Photo' => 'nullable|string|max:500',
            'NumPhoto' => 'nullable|integer|min:0',
        ]);

        $validated = $request->only(['IDHOTEL', 'Libelle', 'Description', 'Photo', 'NumPhoto']);

        $imageId = DB::table('imagehotel')->insertGetId($validated, 'IDIMAGE');

        $image = DB::table('imagehotel')
            ->select(self::COLUMNS)
            ->where('IDIMAGE', $imageId)
            ->first();

        return response()->json($image, 201);
    }

    public function update(Request $request, int $id)
    {
        $this->abortUnless(ImageHotel::whereKey($id)->exists(), 404, 'Image introuvable.');

        $this->validate($request, [
            'IDHOTEL' => 'sometimes|integer|exists:hotel,IDHOTEL',
            'Libelle' => 'nullable|string|max:255',
            'Description' => 'nullable|string|max:1000',
            'Photo' => 'nullable|string|max:500',
            'NumPhoto' => 'nullable|integer|min:0',
        ]);

        $validated = $request->only(['IDHOTEL', 'Libelle', 'Description', 'Photo', 'NumPhoto']);

        DB::table('imagehotel')->where('IDIMAGE', $id)->update($validated);

        $image = DB::table('imagehotel')
            ->select(self::COLUMNS)
            ->where('IDIMAGE', $id)
            ->first();

        return response()->json($image);
    }

    public function destroy(int $id)
    {
        $this->abortUnless(ImageHotel::whereKey($id)->exists(), 404, 'Image introuvable.');

        DB::table('imagehotel')->where('IDIMAGE', $id)->delete();

        return response()->json(null, 204);
    }
}
