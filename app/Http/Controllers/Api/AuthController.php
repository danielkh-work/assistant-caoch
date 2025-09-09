<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\BaseResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserLoggedIn;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([

            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "You have Register SuccessFully", $user,$token);

    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user['permissions'] = $user->getPermissionsViaRoles()->pluck('name');
        $expiresIn = now()->addMinutes(config('sanctum.expiration', 60))->timestamp;
        // Mail::to('aminnoorulamin977@gmail.com')->send(new UserLoggedIn($user));
        // \Log::info(['user'=>$user]);
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Login SuccessFully", $user,$token);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public  function userUpdate(Request $request)
    {

        $user =   User::find(auth()->user()->id);
        $user->name = $request->name;
        $user->fname = $request->last_name;
        $user->email = $request->email;

        if ($request->hasFile('image')) {

            $path =  uploadImage($request->image, 'public');
            $user->image = $path;
        }
        $user->save();
        $user['permissions'] = $user->getPermissionsViaRoles()->pluck('name');
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "User Updated successfully", $user);
    }

    public function changePassword(Request $request)
    {
        $old_pass = auth()->user()->password;
        $user_old_pass = $request->old_password;
        $new_pass = $request->password;

        $request->validate([
            'old_password' => 'required',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user =  auth()->user();

        if ($user && Hash::check($user_old_pass, $old_pass)) {
            $user->password = bcrypt($new_pass);
            $user->save();
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Password updated successfully");

        } else {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, "Old password is incorrect");
        }
    }

    
    public function forgotPassword(Request $request)
    {
        // Validate email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $validator->errors(),'');
        }

        // Send reset password link
        $status = Password::sendResetLink($request->only('email'));
        \Log::info(['resend link'=> $status]);

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email.','success'=>true], 200)
            : response()->json(['error' => 'Unable to send reset link.'], 500);
    }

    public function resetPassword(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return new BaseResponse(STATUS_CODE_UNPROCESSABLE, STATUS_CODE_UNPROCESSABLE, $validator->errors(),'');
        }

        // Reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );
        
      
        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully.'], 200)
            : response()->json(['error' => 'Invalid token or email.'], 500);
    }

    public function viewProfile(Request $request)
    {
        $user  = auth('api')->user();
        $user['permissions'] = $user->getPermissionsViaRoles()->pluck('name');
        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "User List",$user );
    }

    public function profileUpdate(Request $request)
    {
   
        $user  = auth('api')->user();
        $user->name =  $request->name;
        $user->email =  $request->email;
        if($request->hasFile('image')){

          $file =  $request->image;
          $path =   uploadImage($file,'public');
          $user->image =  $path ;
        }
        $user->save();

        return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "User Updated Successfully",$user );
    }

    public function saveSport(Request $request)
    {
       $user = auth('api')->user();
       $user->sport_id = $request->sport_id;
       $user->save();
       return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "User Updated Successfully",$user );
    }

    public function addAssistantCoach(Request $request){

          \Log::info(['data'=>$request->all()]);
            $request->validate([
                'name'     => 'required|string',
                'email'    => 'required|email|unique:users', 
            ]);
            $headCoach = auth()->user();
            if ($headCoach->role !== 'head_coach') {
                abort(403);
            }

            $assistant = User::create([
                'name'          => $request->name,
                'email'         => $request->email,
                'password'      => bcrypt('12345678'),
                'role'          => 'assistant_coach',
                'head_coach_id' => $headCoach->id,
                'sport_id' => $headCoach->sport_id,
                'is_subscribe' => $headCoach->is_subscribe,
                'subscription_id' => $headCoach->subscription_id,
               
            ]);
            $headCoachRoles = $headCoach->roles->pluck('name'); 
            $assistant->assignRole($headCoachRoles); 
            return new BaseResponse(STATUS_CODE_OK, STATUS_CODE_OK, "Add Assistant Coach Successfully",$assistant );
           
    }
}
