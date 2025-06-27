<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class TeamController extends Controller
{
       /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = Team::orderBy('id', 'desc')->get();
        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                // ->addColumn('position',function ($row){
                //     return $row->is_verify==1 ? 'offence' : 'deffence';
                // })
                ->addColumn('action', function($row){
                    return '<a href="' . route('teams.show', ['id' => $row->id]) . '" class="edit btn btn-primary btn-sm">View</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('teams.index',$data);
    }
}
