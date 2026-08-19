<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Taille de page par défaut pour les listes de résultats, alignée sur
     * la pagination "offset/limit" utilisée par le client mobile (30
     * éléments par lot, cf. `xEquat` côté Flutter).
     */
    protected const DEFAULT_LIMIT = 30;
    protected const MAX_LIMIT = 100;

    /**
     * Lit les paramètres de pagination `offset`/`limit` de la requête en
     * les bornant à des valeurs raisonnables.
     *
     * @return array{offset:int, limit:int}
     */
    protected function pagination(Request $request): array
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $limit = (int) $request->query('limit', self::DEFAULT_LIMIT);
        $limit = $limit > 0 ? min($limit, self::MAX_LIMIT) : self::DEFAULT_LIMIT;

        return compact('offset', 'limit');
    }

    /**
     * Dérive un seed stable pour `inRandomOrder()` à partir des filtres de
     * la requête (offset/limit exclus). Deux appels avec les mêmes filtres
     * mais un offset différent obtiennent ainsi le même ordre aléatoire, ce
     * qui rend la pagination stable sans que le client ait à mémoriser ou
     * transmettre explicitement un seed.
     */
    protected function randomSeed(Request $request): string
    {
        $params = $request->query();
        unset($params['offset'], $params['limit']);
        ksort($params);

        return (string) (crc32(json_encode($params)) % 1000000);
    }

    /**
     * Enveloppe standard des réponses de type liste paginée.
     */
    protected function paginatedResponse(iterable $data, int $total, array $pagination)
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'offset' => $pagination['offset'],
                'limit' => $pagination['limit'],
            ],
        ]);
    }

    /**
     * Équivalents de `abort_if()`/`abort_unless()` : ces helpers globaux
     * n'existent pas dans Lumen (ils sont propres à Laravel Foundation),
     * seul `abort()` est disponible.
     */
    protected function abortIf(bool $condition, int $status, string $message): void
    {
        if ($condition) {
            abort($status, $message);
        }
    }

    protected function abortUnless(bool $condition, int $status, string $message): void
    {
        $this->abortIf(! $condition, $status, $message);
    }
}
