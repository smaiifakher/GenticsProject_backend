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
        $query  = PersonPositions::select('timestamp')
                                 ->selectRaw('count(person) as number')
                                 ->selectRaw("group_concat(person, '|', pos_x, '|', pos_y) as details")
                                 ->selectRaw('from_unixtime(timestamp/1000) as datetime')
                                 ->groupBy('timestamp')
                                 ->orderBy('timestamp')
                                 ->get()
                                 ->toArray();
        $result = [];
        foreach ($query as $item) {
            $formattedItem = [];

            $formattedItem['timestamp'] = $item['timestamp'];
            $formattedItem['datetime']  = $item['datetime'];
            $formattedItem['number']    = $item['number'];
            $details                    = explode(',', $item['details']);
            foreach ($details as $detail) {
                $info                        = explode('|', $detail);
                $personWithDetails           = [];
                $personWithDetails['person'] = $info[0];
                $personWithDetails['posX']   = $info[1];
                $personWithDetails['posY']   = $info[2];
                $formattedItem['details'][]  = (object)$personWithDetails;
            }

            $result[] = $formattedItem;
        }
        return response()->json($result, 200);
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
                                 ->selectRaw("group_concat(person, '|', pos_x, '|', pos_y) as details")
                                 ->groupBy('timestamp')
                                 ->orderBy('timestamp')
                                 ->get()
                                 ->toArray();
        $result = [];
        foreach ($query as $item) {
            $formattedItem = [];
            $formattedItem['timestamp'] = $item['timestamp'];
            $formattedItem['datetime']  = $item['datetime'];
            $formattedItem['positions'] = explode(',', $item['positions']);
            $details                    = explode(',', $item['details']);
            foreach ($details as $detail) {
                $info                       = explode('|', $detail);
                $personWithDetails          = new \stdClass();
                $personWithDetails->person  = $info[0];
                $personWithDetails->posX    = $info[1];
                $personWithDetails->posY    = $info[2];
                $formattedItem['details'][] = $personWithDetails;
            }
            $result[] = $formattedItem;
        }
        return response()->json($result, 200);
    }
}
