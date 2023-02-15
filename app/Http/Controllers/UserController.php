<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\CustomHelper\Result;
use Throwable;

class UserController extends Controller
{
    // Get all users
    public function index()
    {
        $users = User::join("roles", "roles.id", "=", "users.role_id")
            ->join("users AS creators", "creators.id", "=", "users.created_by")
            ->leftJoin("attendees", "attendees.user_id", "=", "users.id")
            ->get(["users.*", "roles.name AS role", "creators.name AS created", "attendees.id AS attendee"]);
        return $users;
    }

    // Login
    public function login(Request $request)
    {
        try {

            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ], [
                'email.required' => 'Email address Field is required!',
                'password.required' => 'Password Field is required!'
            ]);

            $newPassword = md5($request->password);

            $userExists = User::where('password', $newPassword)->where('email', $request->email)->first();

            if ($userExists == null) {
                $result = Result::Simple("Invalid Credentials", 400);

                return $result;
            }


            $token = $userExists->createToken('myApp')->plainTextToken;

            // $token = "bad";

            $result = Result::SignIn("Login succesfull", $userExists, $token);

            return $result;
        } catch (\Illuminate\Validation\ValidationException $e) {

            $result = Result::Error($e->validator->errors(), 400);

            return $result;
        }
    }

    // Create user
    public function store(Request $request)
    {

        try {

            $validatedData = $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'phone' => 'required|min:10|unique:users',
                'collegeORunit' => 'required|string',
                'created_by' => 'required|integer'
            ], [
                'name.required' => 'Name field can not be empty!',
                'email.required' => 'Email field can not be empty',
                'email.unique' => 'This email address is already in use',
                'phone.required' => 'Phone number field must be provided',
                'phone.unique' => 'This phone number has already been taken',
                'collegeORunit.required' => 'College/Unit must be provided',
                'created_by.required' => 'The user creating this record must be specified'
            ]);

            $validatedData['role_id'] = 2;

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => 2,
                'created_by' => $request->created_by,
                'college/unit' => $request->collegeORunit
            ];

            User::create($data);

            $result = [
                'msg' => 'Created a new user',
                'StatusCode' => 201
            ];

            return $result;
        } catch (\Illuminate\Validation\ValidationException $e) {

            $result = [
                'msg' => $e->validator->errors(),
                'StatusCode' => 400
            ];

            return $result;
        }
    }

    // Admin creating admin
    public function store_admin(Request $request)
    {
        try {

            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'phone' => 'required|min:10|max:10',
                'pass' => 'required|min:6',
                'passConf' => 'required|min:6',
                'collegeORunit' => 'required|string',
                'created_by' => 'required|integer'
            ], [
                'name.required' => 'Name field is required',
                'role_id.required' => 'A user must have a role',
                'email.required' => 'required|email|unique:users',
                'phone.required' => 'required|min:10',
                'pass.required' => 'required|min:6',
            ]);


            if ($request->pass != $request->passConf) {
                return $result = [
                    'msg' => 'Passwords dont match!',
                    'StatusCode' => 400
                ];
            }

            $data = [
                'name' => $request->name,
                'role_id' => 1,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => md5($request->pass),
                'college/unit' => $request->collegeORunit,
                'created_by' => $request->created_by
            ];

            User::create($data);

            $result = [
                'msg' => 'Created user',
                'StatusCode' => 201
            ];

            return $result;
        } catch (\Illuminate\Validation\ValidationException $e) {

            $result = [
                'msg' => $e->validator->errors(),
                'StatusCode' => 400
            ];

            return $result;
        }
    }

    //Get a user
    public function getUserById($id)
    {

        $user = User::where('id', $id)->get();

        $result[] = [
            'result' => $user,
            'StatusCode' => 200
        ];

        return $result;
    }

    // Edit profile
    public function edit(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
                'name' => 'required',
                'email' => 'required|email',
                'phone' => 'required|min:10|max:10',
                'updated_by' => 'required|int'
            ], [
                'id.required' => 'The user profile being edited isnt specified',
                'name.required' => 'Name field can not be empty',
                'email.required' => 'Email field can not be empty',
                'email.email' => 'Provide a proper email',
                'phone.required' => 'A phone number must be provided',
                'phone.min' => 'A phone number must be 10 characters long',
                'phone.max' => 'A phone number must be 10 characters long',
                'updated_by' => 'The user updating this must be specified!'
            ]);


            User::where('id', $request->id)->update($request->all());
            $user = User::where('id', $request->id)->first();

            $result = [
                'msg' => "Successfully updated users profile",
                'StatusCode' => 204,
                "user" => $user
            ];

            return $result;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $result = [
                'msg' => $e->validator->errors(),
                'StatusCode' => 500
            ];

            return $result;
        }
    }


    // Change password
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
                'oldPass' => 'required',
                'newPass' => 'required|min:6',
                'confPass' => 'required'
            ], [
                'id.required' => 'The user identifier was empty!',
                'oldPass.required' => 'The current password should be provided',
                'newPass.required' => 'Your new password is required',
                'confPass' => 'Password confirmation field is empty!',
                'newPass.min' => 'Password must be greater than 6 characters'
            ]);

            if ($request->newPass !== $request->confPass) {
                return $result = [
                    'msg' => 'Passwords dont match!',
                    'StatusCode' => 404
                ];
            }


            $password = md5($request->oldPass);

            $passwordExists = User::where('id', $request->id)->where('password', $password)->get();

            if (count($passwordExists) < 1) {
                $result = [
                    'msg' => "Incorrect password",
                    'StatusCode' => 404
                ];

                return $result;
            }

            User::where('id', $request->id)->where('password', $password)->update(['password' => md5($request->newPass)]);;

            $result = [
                'msg' => "Successfully updated password",
                'StatusCode' => 204
            ];

            return $result;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $result = [
                'msg' => $e->validator->errors(),
                'StatusCode' => 400
            ];

            return $result;
        }
    }

    // Delete user
    public function Delete(Request $request)
    {
        User::where('id', $request->id)->delete();

        $result = [
            'msg' => 'User successfully deleted',
            'StatusCode' => 204
        ];

        return $result;
    }

    // Logout
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return Result::Simple("Logged out", 200);
    }
}
