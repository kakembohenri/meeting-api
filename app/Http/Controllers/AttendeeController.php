<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CustomHelper\Result;
use App\Models\Attendee;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttendeeController extends Controller
{
    // Fetch all meeting attendees
    public function index()
    {

        $setting = DB::table('settings')->first();

        if ($setting == null) {

            return [];
        }

        $attendees = Attendee::join("users", "users.id", "=", "attendees.user_id")
            ->join("meetings", "meetings.id", "=", "attendees.meeting_id")
            ->get(["attendees.id", "users.name AS name", "meetings.name AS meeting", "meetings.date AS date", "meetings.start_time AS start", "meetings.end_time AS end", "meetings.id AS meeting_id"])->where('meeting_id', $setting->current_meeting);

        return $attendees;
    }

    // Get attendee by id

    public function getAttendeeById($id)
    {

        $exists = Attendee::where('id', $id)->get();

        if (count($exists) < 1) {
            return Result::Simple('Attendee does not exist', 404);
        }

        $attendee = Attendee::join('users', 'users.id', '=', 'attendees.user_id')
            ->where("attendees.id", $id)
            ->first(['attendees.id', 'users.name', 'users.phone', 'users.email']);

        return Result::WithResult($attendee, 200);
    }

    // Get attendees by meeting id
    public function getAttendeesByMeetingId($id)
    {
        $attendees = Attendee::join("users", "users.id", "=", "attendees.user_id")
            ->join("meetings", "meetings.id", "=", "attendees.meeting_id")
            ->get(["attendees.id", "users.name AS name", "meetings.name AS meeting", "meetings.date AS date", "meetings.start_time AS start", "meetings.end_time AS end", "meetings.id AS meeting_id", "users.email AS email", "users.phone AS phone", "users.college/unit AS college/unit"])->where('meeting_id', $id);


        return Result::WithResult($attendees, 200);
    }


    // Create a meeting attendee
    public function store(Request $request)
    {
        try {

            if ($request->email == null) {

                $request->validate([
                    'name' => 'required|string|min:6',
                    'phone' => 'required|string|unique:users|min:10|max:10',
                    "collegeORunit" => "required|string",
                    'created_by' => 'required|integer'
                ], [
                    "name.required" => "The name field can not be empty",
                    'phone.required' => "The phone field can not be empty",
                    "created_by.required" => "The user creating this attendee must be specified",
                    "collegeORunit.required" => "College/Unit to which a user belongs is required"
                ]);
            } else {
                $request->validate([
                    'name' => 'required|string|min:6',
                    'phone' => 'required|string|unique:users|min:10|max:10',
                    'email' => 'required|email|unique:users',
                    "created_by" => "required|integer",
                    "collegeORunit" => "required|string"

                ], [
                    "name.required" => "The name field can not be empty",
                    'phone.required' => "The phone field can not be empty",
                    "created_by.required" => "The user creating this attendee must be specified",
                    "collegeORunit.required" => "College/Unit to which a user belongs is required"

                ]);
            }

            // Get current meeting
            $setting = DB::table("settings")->first();

            if ($setting == null) {
                $result = [
                    'msg' => 'You havent set a current meeting yet',
                    'StatusCode' => 400
                ];
                return $result;
            }

            // Create a user

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'college/unit' => $request->collegeORunit,
                'created_by' => $request->created_by,
                'role_id' => 2
            ];

            $user = User::create($data);


            Attendee::create([
                'user_id' => $user->id,
                'meeting_id' => $setting->current_meeting,
                'created_by' => $request->created_by
            ]);

            return Result::Simple("User has been created and registered as an attendee of the current meeting", 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Result::Simple($e->validator->errors(), 404);
        }
    }

    // Add already present user to the attendee list
    public function addUserToAttendee(Request $request)
    {
        try {

            $request->validate([
                "user_id" => 'required|integer',
                "created_by" => 'required|integer'
            ], [
                "user_id.required" => "The user id is a required field!",
                "created_by.required" => "The user Registering this attendee is required",
            ]);

            // Get current meeting
            $setting = DB::table("settings")->first();

            if ($setting == null) {
                $result = [
                    'msg' => 'You havent set a current meeting yet',
                    'StatusCode' => 400
                ];
                return $result;
            }
            // Create a user
            $request["meeting_id"] = $setting->current_meeting;

            $alreadyExists = Attendee::where('user_id', $request->user_id)->where('meeting_id', $setting->current_meeting)->get();


            if (count($alreadyExists) > 0) {
                return Result::Simple("This user has already been registered for the current meeting!", 400);
            } else {

                Attendee::create($request->all());
            }

            return Result::Simple("Successfully registered an old user to the current meeting", 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Result::Simple($e->validator->errors(), 400);
        }
    }

    public function delete($id)
    {

        $exists = Attendee::where('id', $id)->get();

        if (count($exists) < 1) {
            return Result::Simple('Attendee does not exist', 404);
        }

        Attendee::where('id', $id)->delete();

        return Result::Simple("Successfully deleted attendee", 204);
    }
}
