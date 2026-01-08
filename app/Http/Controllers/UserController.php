<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Player;
use App\Models\User;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetByAdmin;
class UserController extends Controller
{
       public function index(Request $request)
    {

        

        $query = User::orderBy('id', 'desc');
       
       
        $data = $query->get();
        if ($request->ajax()) {
         
            return DataTables::of($data)
                ->addIndexColumn()
                // ->addColumn('position',function ($row){
                //     return $row->is_verify==1 ? 'offence' : 'deffence';
                // })
                //   ->addColumn('roles', function($row) {
                //             return $row->roles->pluck('name')->implode(', ');
                //     })
                //    ->addColumn('created_by', function($row) {
                //             return $row->user->name ?? 'admin';
                //     })
            ->addColumn('action', function ($row) {
    $approveUrl = route('users.approve', ['id' => $row->id]);
    $rejectUrl  = route('users.reject', ['id' => $row->id]);
    $resetUrl   = route('users.reset_password', ['id' => $row->id]);

    $buttons = '';

    // Pending user → show Approve & Reject only
     if ($row->status == 'pending') {
        $buttons .= '
            <a href="' . $approveUrl . '" class="btn btn-success btn-sm me-1">Approve</a>
            <a href="' . $rejectUrl . '" class="btn btn-danger btn-sm me-1">Reject</a>
        ';
     }
    // Approved / non-pending → show Reset Password only
    if ($row->status !== 'pending') {

        //  $buttons .= '
        //     <a href="' . $approveUrl . '" class="btn btn-success btn-sm me-1">Approve</a>
        //     <a href="' . $rejectUrl . '" class="btn btn-danger btn-sm me-1">Reject</a>
        // ';
        $buttons .= '
            <form action="' . $resetUrl . '" method="POST" style="display:inline;">
                ' . csrf_field() . '
                <button type="submit" class="btn btn-warning btn-sm">
                    Reset Password
                </button>
            </form>
        ';
    }

    return $buttons;
})


//  <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                    //         ' . csrf_field() . '
                    //         ' . method_field('DELETE') . '
                    //         <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                    //     </form>
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('users.index',$data);
    }
   
    public function showResetPasswordForm($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.reset-password', compact('user'));
    }
     public function approve($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'approved'; // or status column
        $user->save();

        return redirect()->back()->with('success', 'User approved successfully!');
    }

        public function reject($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'rejected';
        $user->save();

        return redirect()->back()->with('error', 'User rejected!');
    }


    public function adminResetPassword($id)
    {
        
        $user = User::findOrFail($id);
        $newPassword = Str::random(8);
        $user->password = Hash::make('Robert1001!');
        $user->save();
        // Mail::to($user->email)->send(new PasswordResetByAdmin($user, $newPassword));
        return redirect()->back()->with('success', 'Password has been reset.');
    }

     public function resetAllUserPasswords()
    {
     
        $newPassword = 'Robert1001!';

        // Update all users except admin
        User::where('email', '!=', 'admin@admin.com')->update([
            'password' => Hash::make($newPassword),
          
        ]);

        return redirect()->back()->with('success', 'All user passwords have been reset (except admin).');
    }

    
}

   
