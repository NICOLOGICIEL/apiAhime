<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Securise les endpoints d'execution SQL generique de ApiController :
 *  1. cle d'API obligatoire : header X-API-Key compare a API_ACCESS_KEY (env) ;
 *  2. lecture seule : seuls des SELECT simples (un seul statement) sont acceptes.
 */
trait GuardedApi
{
    /**
     * Verifie la cle d'API puis que chaque champ Requete* est un SELECT simple.
     * Retourne une Response d'erreur JSON, ou null si tout est valide.
     */
    protected function guard(Request $request, array $data)
    {
        $expected = (string) env('API_ACCESS_KEY');

        if ($expected === '') {
            return response()->json(['error' => 'Serveur mal configure : API_ACCESS_KEY manquante'], 500);
        }

        if (! hash_equals($expected, (string) $request->header('X-API-Key'))) {
            return response()->json(['error' => 'Cle d API invalide ou absente (header X-API-Key)'], 401);
        }

        foreach ($data as $key => $value) {
            if (is_string($value) && stripos($key, 'Requete') === 0) {
                $reason = $this->refusedReason($value);

                if ($reason !== null) {
                    return response()->json(['error' => $reason], 403);
                }
            }
        }

        return null;
    }

    private function refusedReason(string $sql): ?string
    {
        $trimmed = rtrim(trim($sql), '; \t\n\r\0');

        if ($trimmed === '') {
            return 'Requete vide.';
        }

        if (! preg_match('/^SELECT\s/i', $trimmed)) {
            return 'Seules les requetes SELECT sont autorisees.';
        }

        // Un seul statement, aucune ecriture, ni DDL/DCL, ni acces systeme.
        $banned = '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|RENAME|REPLACE|GRANT|REVOKE|LOCK|CALL|SET|USE|OUTFILE|LOAD_FILE|INFORMATION_SCHEMA|MYSQL\.)\b/i';

        if (strpos($trimmed, ';') !== false || preg_match($banned, $trimmed)) {
            return 'La requete contient une instruction interdite.';
        }

        return null;
    }
}
