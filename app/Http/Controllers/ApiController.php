<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiController extends \Laravel\Lumen\Routing\Controller
{
    public function action(Request $request)
    {
        $data = $request->all();

        $action = $data['data_action'] ?? null;

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
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $sql = $data['Requete'];

        try {
            $results = DB::select($sql);

            $total = count($results);

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
            $result1 = DB::select($sql1);
            $result2 = $sql2 ? DB::select($sql2) : [];
            $result3 = $sql3 ? DB::select($sql3) : [];

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
            $results = DB::select($sql);

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur SQL',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
