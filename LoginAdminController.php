<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Hash;
use Validator;
use Auth;

class LoginAdminController extends Controller
{

     public function adminDashboard()
     {
        try {
         $users = Admin::all();
         $success =  $users;
        }
        catch (OAuthServerException $e) {
          error_log($e->getHint()); // add this line to know the actual error
          throw new AuthenticationException;
        }
        return response()->json($success, 200);
     }

      public function adminLogin(Request $request)
      {
         $validator = Validator::make($request->all(), [
             'email' => 'required|email',
             'password' => 'required',
         ]);

         if($validator->fails()){
             return response()->json(['error' => $validator->errors()->all()]);
         }

         if(auth()->guard('admin')->attempt(['email' => request('email'), 'password' => request('password')])){

             config(['auth.guards.api.provider' => 'admin']);

             $admin = Admin::select('admins.*')->find(auth()->guard('admin')->user()->id);
             $success =  $admin;
             $success['token'] =  $admin->createToken('realEstate',['admin'])->accessToken;

             return response()->json($success, 200);
         }else{
             return response()->json(['error' => ['Admin Email and Password are Wrong.']], 403);
         }
       }

       public function adminLogout(Request $request) {
         $result = $request->user()->token()->revoke();
         if($result){
               $response = response()->json(['message'=>'Admin logout successfully'],200);
         }else{
           $response = response()->json(['message'=>'Something is wrong.'],400);
         }
         return $response;
       }

       public function checkIfValidToken() {
         if(Auth::guard('admin-api')->check() && (Auth::guard('admin-api')->user()->token()->scopes[0] === "admin")) {
           return response()->json(['status' => true]);
         }
         return response()->json(['status' => false]);
       }


}
