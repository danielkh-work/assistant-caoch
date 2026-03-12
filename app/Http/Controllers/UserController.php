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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rules\Password;

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

                 ->addColumn('password', function ($row) {
        try {
            return $row->encrypted_password 
                ? Crypt::decryptString($row->encrypted_password) 
                : '-';
        } catch (\Exception $e) {
            return '-';
        }
    })
            ->addColumn('action', function ($row) {
    $approveUrl = route('users.approve', ['id' => $row->id]);
    $rejectUrl  = route('users.reject', ['id' => $row->id]);
    $resetUrl   = route('users.reset_password', ['id' => $row->id]);
    $deleteUrl  = route('users.destroy', $row->id);
    $buttons = '';

    // Pending user → show Approve & Reject only
     if ($row->status == 'pending') {
        $buttons .= '
            <a href="' . $approveUrl . '" class="btn btn-success btn-sm me-1">Approve</a>
            <a href="' . $rejectUrl . '" class="btn btn-secondary btn-sm me-1">Reject</a>
        ';
     }
    // Approved / non-pending → show Reset Password only
    if ($row->status !== 'pending') {
        
         $changePasswordUrl = route('users.change-password', $row->id);
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

        <a href="' . $changePasswordUrl . '" class="btn btn-primary btn-sm">
            Change Password
        </a>
        ';
    }

      $buttons .= '
        <form action="'.$deleteUrl.'" method="POST" style="display:inline;"
              onsubmit="return confirm(\'Are you sure you want to delete this user?\')">
            '.csrf_field().'
            '.method_field('DELETE').'
            <button type="submit" class="btn btn-danger btn-sm">
                Delete
            </button>
        </form>
    ';

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

   public function changePassword(User $user)
    {
        return view('users.change-password', compact('user'));
    }
    public function updatePassword(Request $request, User $user)
    {

          
            $request->validate([
                'password' => 'required|min:6|confirmed',
            ], [
                'password.confirmed' => 'Password and Confirm Password do not match.'
            ]);

            // Find user
            $user = User::findOrFail($user->id);

            // Update password
            $user->encrypted_password = Crypt::encryptString($request->password);
            $user->password = Hash::make($request->password);
            
            $user->save();

            return redirect()->back()->with('success', 'Password updated successfully.');
       
        // $validated = $request->validate([
        //     'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        // ]);
        //  dd(132);

        // $user->password = Hash::make($request->password);
       
        // $user->save();

        // return redirect()->back()->with('success', 'Password updated successfully.');
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

    public function destroy(User $user)
    {

       
        $user->delete();

        return redirect()->route('users.index')
                        ->with('success', 'User deleted successfully.');
    }

    
}

   
