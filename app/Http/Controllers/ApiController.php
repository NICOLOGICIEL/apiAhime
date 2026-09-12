<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\GuardedApi;
use Illuminate\Support\Facades\Validator;

class ApiController extends \Laravel\Lumen\Routing\Controller
{
    use GuardedApi;

    public function action(Request $request)
    {
        $data = $request->all();

        $action = $data['data_action'] ?? null;

        // Securite : cle d API obligatoire (header X-API-Key) + restriction lecture seule.
        $response = $this->guard($request, $data);
        if ($response !== null) {
            return $response;
        }


        if (!$action) {
            return response()->json(['error' => 'data_action requis'], 400);
        }

        switch ($action) {
            case 'ReqExec':
                return $this->reqExec($data);
            case 'ReqMultiExec':
                return $this->reqMultiExec($data);
            case 'EnvoiRequete':
                return $this->envoiRequete($data);
            default:
                return response()->json(['error' => 'Action non reconnue'], 400);
        }
    }

    private function reqExec(array $data)
    {
        $validator = Validator::make($data, [
            'Requete' => 'required|string',
            'Requete2' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $sql = $data['Requete'];
        $sqlTotal = $data['Requete2'] ?? null;

        try {
            $results = $this->cachedSelect($sql);

            $total = $sqlTotal ? count($this->cachedSelect($sqlTotal)) : count($results);

            return response()->json([
                'result' => $results,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur SQL',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function reqMultiExec(array $data)
    {
        $validator = Validator::make($data, [
            'Requete1' => 'required|string',
            'Requete2' => 'nullable|string',
            'Requete3' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $sql1 = $data['Requete1'];
        $sql2 = $data['Requete2'] ?? null;
        $sql3 = $data['Requete3'] ?? null;

        try {
            $result1 = $this->cachedSelect($sql1);
            $result2 = $sql2 ? $this->cachedSelect($sql2) : [];
            $result3 = $sql3 ? $this->cachedSelect($sql3) : [];

            $total = count($result1) + count($result2) + count($result3);

            $response = [
                'result1' => $result1,
                'result2' => $result2,
                'total' => $total,
            ];

            if ($sql3) {
                $response['result3'] = $result3;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur SQL',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function envoiRequete(array $data)
    {
        $validator = Validator::make($data, [
            'Requete' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $sql = $data['Requete'];

        try {
            $results = $this->cachedSelect($sql);

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur SQL',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute un SELECT en passant par le cache Redis (cle = hash du SQL).
     * GuardedApi garantit que $sql est un SELECT simple et sans effet de bord,
     * ce qui rend la mise en cache par contenu de la requete sure.
     */
    private function cachedSelect(string $sql): array
    {
        $ttl = (int) env('API_CACHE_TTL', 0);

        if ($ttl <= 0) {
            return DB::select($sql);
        }

        $key = 'sql_select:'.hash('sha256', $sql);

        try {
            return Cache::remember($key, $ttl, function () use ($sql) {
                return DB::select($sql);
            });
        } catch (\Throwable $e) {
            // Redis indisponible : on degrade sans cache plutot que de faire
            // echouer l'endpoint (utile en particulier en hebergement mutualise
            // ou le service Redis n'est pas garanti).
            report($e);

            return DB::select($sql);
        }
    }
}
