<?php

namespace App\Http\Controllers\Services;

use App\Models\User;
use App\Models\Property;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\UserNotFoundException;

class UserService extends Controller
{
    public function getUser()
    {
      try {
        $user = User::select('users.*')->findOrFail(Auth::guard('user-api')->user()->id);
      }
      catch (Throwable $e) {
         throw new UserNotFoundException('User not found');
      }
      return $user;
    }

    public function getUserById(int $id) {
      try {
        $user = User::select('name', 'phone_number', 'photo')->findOrFail($id);
      }
      catch (Throwable $e) {
         throw new UserNotFoundException('User not found');
      }
      return $user;
    }

    public function getUserByPropertyId(int $id) {
      try {
        $property = Property::findOrFail($id);
        $user = $property->user()->select('phone_number')->first();
      }
      catch (Throwable $e) {
         throw new UserNotFoundException('User not found');
      }
      return $user;
    }

}
