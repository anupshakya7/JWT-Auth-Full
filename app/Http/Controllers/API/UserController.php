<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class UserController extends Controller
{
    //Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
             'name' => 'required|string|min:2|max:100',
             'email' => 'required|string|email|max:100|unique:users',
             'password' => 'required|string|min:6|confirmed'
         ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => "User Register Successfully",
            'user' => $user
        ]);
    }

    //Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
            'password' => 'required|string|min:6'
        ]);

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if(!$token = auth()->guard('api')->attempt($validator->validated())) {
            return response()->json([
                'success' => false,
                'msg' => 'Username & Password is Incorrect'
            ]);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    //Logout
    public function logout()
    {
        try {
            auth()->logout();
            return response()->json([
                'success' => true,
                'message' => 'User Logout Successfully'
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    //Profile
    public function profile()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => auth()->user()
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    //Update Profile
    public function updateProfile(Request $request)
    {
        if(auth()->user()) {
            $validator = Validator::make($request->all(), [
                'id' => "required",
                'name' => 'required|string',
                'email' => 'required|email|string'
            ]);

            if($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = User::find($request->id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();
            return response()->json([
                'success' => true,
                'data' => $user
            ]);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'User is not Authenticated'
            ]);
        }
    }

    //Verify Email
    public function verifyMail($email)
    {
        if(auth()->user()) {
            $user = User::where('email', $email)->get();
            if(count($user) > 0) {
                $random = Str::random(40);
                $domain = URL::to('/');
                $url = $domain.'/mail-verify/'.$random;

                $data['url'] = $url;
                $data['email'] = $email;
                $data['title'] = "Email Verification";
                $data['body'] = "Please click here to verify your mail.";

                Mail::send('mail.verify-email', ['data' => $data], function ($message) use ($data) {
                    $message->to($data['email'])->subject($data['title']);
                });

                $user = User::find($user[0]['id']);
                $user->remember_token = $random;
                $user->save();

                return response()->json(['success' => true,'message' => 'Mail sent Successfully']);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User Not Found'
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User is not Authenticated'
            ]);
        }
    }

    public function mailVerification($token)
    {
        $user = User::where('remember_token', $token)->get();
        if(count($user) > 0) {
            $datetime = Carbon::now()->format('Y-m-d H:i:s');
            $user = User::find($user[0]['id']);
            $user->remember_token = '';
            $user->is_verified = 1;
            $user->email_verified_at = $datetime;
            $user->save();

            return "<h1>Email Verified Successfully</h1>";
        } else {
            return view('mail-error.404');
        }
    }

    //Refresh Token
    public function refreshToken()
    {
        if(auth()->user()) {
            return $this->respondWithToken(auth()->refresh());
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User is not Authenticated.'
            ]);
        }
    }

    //Forget Password
    public function forgotPassword(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();
            if($user) {
                $token = Str::random(40);
                $domain = URL::to('/');
                $url = $domain.'/reset-password?token='.$token;

                $data['url'] = $url;
                $data['email'] = $request->email;
                $data['title'] = "Password Reset";
                $data['body'] = "Please click on below link to reset your password.";

                Mail::send('mail.forgetPassword-email', ['data' => $data], function ($message) use ($data) {
                    $message->to($data['email'])->subject($data['title']);
                });

                $datetime = Carbon::now()->format('Y-m-d H:i:s');
                PasswordReset::updateOrCreate([
                    'email' => $request->email
                ], [
                    'email' => $request->email,
                    'token' => $token,
                    'created_at' => $datetime
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Please check your mail to Reset your Password'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User Not Found'
                ]);
            }
        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    //Reset Password View Load
    public function resetPasswordLoad(Request $request)
    {
        $resetData = PasswordReset::where('token', $request->token)->first();
        if(!$resetData) {
            return view('mail-error.404');
        }
        if(isset($request->token) && !empty($resetData)) {
            $user = User::where('email', $resetData['email'])->first();
            return view('forgetPassword.resetPassword', compact('user'));
        } else {
            return view('mail-error.404');
        }
    }

    //Password Reset
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed'
        ]);

        $user = User::find($request->id);
        $user->password = $request->password;
        $user->save();

        PasswordReset::where('email', $user->email)->delete();

        return "<h1>Your Password has been Reset Successfully!!!</h1>";
    }
}
