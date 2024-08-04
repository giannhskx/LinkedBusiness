<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class BoardgameController extends Controller
{
    public function getHighestScoreBoardgame(Request $request)
    {
        $boardgameIds = $request->input('boardgame_ids');

        if (!is_array($boardgameIds) || empty($boardgameIds)) {
            return response()->json(['error' => 'Invalid input.'], 400);
        }

        $client = new Client();
        $highestScore = -1;
        $bestBoardgame = null;

        foreach ($boardgameIds as $id) {
            try {
                $response = $client->get("https://boardgamegeek.com/xmlapi2/thing?id={$id}");
                $xml = simplexml_load_string($response->getBody()->getContents());
                $json = json_encode($xml);
                $data = json_decode($json, true);

                if (isset($data['item']['statistics']['rating']['average'])) {
                    $score = $data['item']['statistics']['rating']['average'];
                    if ($score > $highestScore) {
                        $highestScore = $score;
                        $bestBoardgame = [
                            'title' => $data['item']['name'],
                            'score' => $score
                        ];
                    }
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error fetching boardgame data.'], 500);
            }
        }

        if (!$bestBoardgame) {
            return response()->json(['error' => 'No valid boardgames found.'], 404);
        }

        return response()->json($bestBoardgame);
    }
}
