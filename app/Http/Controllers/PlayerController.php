<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;

class PlayerController extends Controller
{
       /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = Player::orderBy('id', 'desc')->get();
        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('position',function ($row){
                    return $row->is_verify==1 ? 'offence' : 'deffence';
                })
                ->addColumn('action', function($row){
                    return '<a href="' . route('players.show', ['id' => $row->id]) . '" class="edit btn btn-primary btn-sm">View</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('players.index',$data);
    }
    public function create(){

            $roles =  Role::all();
        return view('players.create',compact('roles'));
    }
    public function store(Request $request)
    {
      
        DB::beginTransaction();
        try {
            $player = new Player();
            $player->name = $request->title;
            $player->number=  $request->number;
            $player->position = $request->position;
            $player->size= $request->size;
            $player->speed= $request->speed;
            $player->weight= $request->weight;
            $player->height= $request->height;
            $player->dob= $request->dob;
            $player->ofp= $request->ofp;
            $player->ofp= $request->ofp;
            $player->strength =  $request->strength;
            $player->position_value =  $request->positionValue;
            if($request->hasFile('image'))
            {
                $path =  uploadImage($request->image,'player');
                $player->image =$path;
            }
            $player->save();
            DB::commit();
            $player->roles()->sync($request->role_id); // assign to multiple roles
            DB::commit();

            return view('players.create')->with('success', 'Player added successfully!');     
        } catch (\Exception $th) {
            DB::rollBack();
            dd($th);
        }
    }
    public function show($id)
    {
        $customer = Player::find($id);

        return view('players.show', compact('customer'));
    }
}
