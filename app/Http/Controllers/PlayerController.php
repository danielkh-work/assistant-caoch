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

       
        $query = Player::with('roles')->orderBy('id', 'desc');
       
        if ($request->filled('role')) {
              
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }
        $data = $query->get();
        if ($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('position',function ($row){
                    return $row->is_verify==1 ? 'offence' : 'deffence';
                })
                  ->addColumn('roles', function($row) {
                            return $row->roles->pluck('name')->implode(', ');
                    })
                  ->addColumn('action', function($row){
                    $editUrl = route('players.edit', ['id' => $row->id]);
                    $deleteUrl = route('players.destroy', ['id' => $row->id]);

                    return '
                        <a href="' . $editUrl . '" class="btn btn-warning btn-sm me-1">Edit</a>
                        <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                        </form>
                    ';
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
    public function edit($id)
    {
        $player = Player::with('roles')->findOrFail($id); // Load player and its roles
        $roles = Role::all(); // All available roles

        return view('players.edit', compact('player', 'roles'));
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
            
            return redirect()->route('players.index');
          
        } catch (\Exception $th) {
            DB::rollBack();
            dd($th);
        }
    }
    public function update(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $player = Player::findOrFail($id);

        $player->name = $request->title;
        $player->number = $request->number;
        $player->position = $request->position;
        $player->size = $request->size;
        $player->speed = $request->speed;
        $player->weight = $request->weight;
        $player->height = $request->height;
        $player->dob = $request->dob;
        $player->ofp = $request->ofp;
        $player->strength = $request->strength;
        $player->position_value = $request->positionValue;

        if ($request->hasFile('image')) {
            $path = uploadImage($request->image, 'player');
            $player->image = $path;
        }

        $player->save();

        // Sync roles (many-to-many polymorphic)
        $player->roles()->sync($request->role_id);

        DB::commit();

        return redirect()->route('players.index')->with('success', 'Player updated successfully.');
    } catch (\Exception $th) {
        DB::rollBack();
        return redirect()->back()->with('error', $th->getMessage());
    }
}
public function destroy($id)
{
    DB::beginTransaction();

    try {
        $player = Player::findOrFail($id);

        // Optionally: delete image file from storage
        if ($player->image && file_exists(public_path('uploads/' . $player->image))) {
            unlink(public_path('uploads/' . $player->image));
        }

        // Detach roles (if it's a polymorphic many-to-many)
        $player->roles()->detach();

        // Delete the player
        $player->delete();

        DB::commit();

        return redirect()->route('players.index')->with('success', 'Player deleted successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to delete player: ' . $e->getMessage());
    }
}

    public function show($id)
    {
        $customer = Player::find($id);

        return view('players.show', compact('customer'));
    }
}
