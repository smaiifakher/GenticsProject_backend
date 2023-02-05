<?php

namespace App\Http\Controllers;

use App\Models\PersonPositions;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PersonPositionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNumberOfPerson(Request $request)
    {
        $query = PersonPositions::select('timestamp')
                                ->selectRaw('count(person) as number')
                                ->selectRaw('from_unixtime(timestamp/1000) as datetime')
                                ->groupBy('timestamp')
                                ->orderBy('timestamp')
                                ->get()
                                ->toArray();


        return response()->json($query, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPositionX()
    {
        $query  = PersonPositions::select('timestamp')
                                 ->selectRaw('group_concat(pos_x) as positions')
                                 ->selectRaw('from_unixtime(timestamp/1000) as datetime')
                                 ->groupBy('timestamp')
                                 ->orderBy('timestamp')
                                 ->get()
                                 ->toArray();
        $result = [];
        foreach ($query as $item) {
            $formattedItem['timestamp'] = $item['timestamp'];
            $formattedItem['datetime']  = $item['datetime'];
            $formattedItem['positions'] = explode(',', $item['positions']);
            $result[]                   = $formattedItem;
        }
        return response()->json($result, 200);
    }
}
