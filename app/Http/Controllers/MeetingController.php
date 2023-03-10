<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use App\CustomHelper\Result;
use App\Models\Attendee;
use App\Models\GuestPreacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MeetingController extends Controller
{
    public function index()
    {
        $meetings = Meeting::join('users', 'users.id', '=', 'meetings.created_by')
            ->join('meeting_status AS statuses', "meetings.status", "=", "statuses.id")
            ->leftJoin("settings", "settings.current_meeting", "=", "meetings.id")
            ->latest()
            ->get(['meetings.*', 'users.name AS created_by', "statuses.name AS status_name", "settings.current_meeting"]);


        return $meetings;
    }

    public function getById($id)
    {
        $meeting = Meeting::join("meeting_status", "meeting_status.id", "=", "meetings.status")
            ->get(["meetings.*", "meeting_status.name AS status"])->where("id", $id)[2];

        $guest = GuestPreacher::where('meeting_id', $id)->first();

        $data[] = [
            'meeting' => $meeting,
            'guest' => $guest
        ];

        return Result::WithResult($data, 200);
    }


    public function getCurrent()
    {
        $setting = DB::table('settings')->first();

        if ($setting === null) {
            $result = [
                'meeting' => null,
                'guest' => null,
                'attendees' => null,
                'StatusCode' => 200
            ];
            return $result;
        }

        $meeting = Meeting::join('users', 'users.id', '=', 'meetings.created_by')
            ->where('meetings.id', $setting->current_meeting)
            ->first(['meetings.*', 'users.name AS created_by', 'users.name AS updated_by']);


        $guest = GuestPreacher::where('meeting_id', $meeting->id)->first();

        $attendees = Attendee::where('meeting_id', $meeting->id)->get();

        $result = [
            'meeting' => $meeting,
            'guest' => $guest,
            'attendees' => count($attendees),
            'StatusCode' => 200
        ];

        return $result;
    }

    public function edit(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
                'name' => 'required',
                'date' => 'required|string',
                'status' => 'required|integer',
                'start_time' => 'required|string',
                'end_time' => 'required|string',
                'location' => 'required',
                'updated_by' => 'required|integer'
            ], [
                'id.required' => 'Meeting to be edited must be specified',
                'name.required' => 'The meeting name must be provided',
                'start_time.required' => 'The starting time of the meeting must be provided',
                'end_time.rrequired' => 'The ending time of the meeting must be provided',
                'location' => 'The meeting venue should be provided',
                'updated_by' => 'The user creating the meeting should be specified',
                'status.required' => 'The status of the meeting should be provided'
            ]);

            Meeting::where('id', $request->id)->update($request->all());

            switch ($request->status) {
                case 2:
                    return Result::Simple("Meeting has taken place", 204);
                case 3:
                    return Result::Simple("Meeting has been cancelled", 204);
                default:
                    return Result::Simple("Meeting has been updated", 204);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Result::Simple($e->validator->errors(), 404);
        }
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
                'doc' => 'required',
                'name' => 'required',
                // 'doc' => 'required|mimes:doc,docx,pdf,txt,csv|max:2048',
                'updated_by' => 'required'
            ]);

            //your base64 encoded data
            $file_64 = $request->doc;

            // .txt .doc .pdf .csv .docx

            $extensions = array('pdf', 'plain', 'vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'vnd.openxmlformats-officedocument.wordprocessingml.document', 'msword');

            $extension = explode('/', explode(':', substr($file_64, 0, strpos($file_64, ';')))[1])[1];

            if (!in_array($extension, $extensions)) {
                $result = [
                    'msg' => 'Wrong file extension',
                    'StatusCode' => 400,
                    'extension' => $extension
                ];
                return $result;
            }

            $replace = substr($file_64, 0, strpos($file_64, ',') + 1);

            // find substring fro replace here eg: data:image/png;base64,

            $file = str_replace($replace, '', $file_64);

            $file = str_replace(' ', '+', $file);

            $fileName = null;
            if ($extension == "plain") {
                $fileName = $request->name . '-' . date('d-M-Y') . '.' . 'txt';
            } else if ($extension == 'vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $fileName = $request->name . '-' . date('d-M-Y') . '.' . 'xlsx';
            } else if ($extension == 'msword') {
                $fileName = $request->name . '-' . date('d-M-Y') . '.' . 'doc';
            } else if ($extension == 'vnd.openxmlformats-officedocument.wordprocessingml.document') {
                $fileName = $request->name . '-' . date('d-M-Y') . '.' . 'docx';
            } else {
                $fileName = $request->name . '-' . date('d-M-Y') . '.' . $extension;
            }

            Storage::disk('public')->put('/apiFiles/' . $fileName, base64_decode($file));

            Meeting::where('id', $request->id)->update(['minutes' => $fileName, 'updated_by' => $request->updated_by]);

            $result = [
                'msg' => 'Successfully uploaded minutes',
                'StatusCode' => 204
            ];

            return $result;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Result::Simple($e->validator->errors(), 404);
        }
    }

    public function download($fileName)
    {
        $myFile = Storage::disk('public')->get('/apiFiles/' . $fileName);

        $extension = explode('.', $fileName)[1];

        // $headers = array('Content-Type: application/pdf');

        $fileName = explode('.', $fileName)[0] . time() . '.' . $extension;

        // return response()->download($myFile, $fileName, $headers);
        $prefix = null;
        if ($extension == "txt") {
            $prefix = 'data:text/plain;base64,';
        } else if ($extension == 'xlsx') {
            $prefix = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,';
        } else if ($extension == 'doc') {
            $prefix = 'data:application/msword;base64,';
        } else if ($extension == 'docx') {
            $prefix = 'data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,';
        } else {
            $prefix = 'data:application/pdf;base64,';
        }

        return [
            'doc' => $prefix . base64_encode($myFile),
            'fileName' => $fileName
        ];

        // $result = [
        //     'file' => base64_encode($file)
        // ];
        // return $result;
    }

    public function store(Request $request)
    {

        try {
            $request->validate([
                'name' => 'required',
                'date' => 'required',
                'start_time' => 'required|string',
                'end_time' => 'required|string',
                'location' => 'required',
                'created_by' => 'required|integer'
            ], [
                'name.required' => 'The meeting name must be provided',
                'start_time.required' => 'The starting time of the meeting must be provided',
                'end_time.rrequired' => 'The ending time of the meeting must be provided',
                'location' => 'The meeting venue should be provided',
                'created_by' => 'The user creating the meeting should be specified'
            ]);

            $data = [
                'name' => $request->name,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'location' => $request->location,
                'created_by' => $request->created_by,
                'status' => 1
            ];

            $meeting = Meeting::create($data);

            if ($request->guest['id'] != "") {
                // Update guest
                GuestPreacher::where('id', $request->guest['id'])->update(['meeting_id' => $meeting->id]);
            }

            return Result::Simple("Meeting has been created", 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $result = [
                'msg' => $e->validator->errors(),
                'StatusCode' => 400
            ];
            return $result;
        }
    }

    public function delete($id)
    {
        Meeting::where('id', $id)->delete();

        return Result::Simple("Meeting has been deleted", 204);
    }

    public function setCurrentMeeting($id)
    {
        try {

            $meeting = Meeting::where("id", $id)->get();

            if (count($meeting) < 1) {
                return Result::Simple("Invalid meeting id", 404);
            } else {
                // Set this meeting as current in settings table
                $settings = DB::table('settings')->get();

                if (count($settings) < 1) {
                    // Add to seetings table
                    DB::table('settings')->insert([
                        'current_meeting' => $id,
                    ]);
                } else {
                    // Update the current meeting in settings table
                    DB::table('settings')->delete();
                    DB::table('settings')->insert([
                        'current_meeting' => $id,
                    ]);
                }

                return Result::Simple("Current meeting has been set", 201);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Result::WithResult($e->validator->errors(), 404);
        }
    }
}
