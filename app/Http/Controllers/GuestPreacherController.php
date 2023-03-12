<?php

namespace App\Http\Controllers;

use App\Models\GuestPreacher;
use Illuminate\Http\Request;
use App\CustomHelper\Result;
use Illuminate\Support\Facades\DB;

class GuestPreacherController extends Controller
{
    public function index()
    {

        $guests = GuestPreacher::join("meetings", "meetings.id", "=", "guest_preachers.meeting_id")
            ->join("invitation_statuses AS statuses", "statuses.id", "=", "guest_preachers.invitation_status")
            ->latest()
            ->get(["guest_preachers.*", "meetings.name AS meeting", "statuses.name AS status"]);
        return $guests;
    }

    // Create a guest
    public function store(Request $request)
    {

        try {
            if ($request->email != null) {
                $request->validate([
                    'name' => 'required',
                    'topic' => 'required|min:5',
                    'email' => 'required|email|unique:guest_preachers',
                    'phone' => 'required|unique:guest_preachers|min:10|max:10',
                    'church_from' => 'required',
                    'created_by' => 'required|integer',
                ]);
            } else {
                $request->validate([
                    'name' => 'required',
                    'topic' => 'required|min:5',
                    'phone' => 'required|unique:guest_preachers|min:10|max:10',
                    'church_from' => 'required|string',
                    'created_by' => 'required|integer',
                ]);
            }

            if ($request->meeting['id'] != "") {
                $request['meeting_id'] = $request->meeting['id'];

                $request['invitation_status'] = 1;

                GuestPreacher::create($request->all());

                return Result::Simple('Guest created', 201);
            }

            $setting = DB::table('settings')->first();

            if ($setting === null) {
                $result = [
                    'msg' => 'Current meeting must be set first',
                    'StatusCode' => 400
                ];

                return $result;
            }

            $request['meeting_id'] = $setting->current_meeting;
            $request['invitation_status'] = 1;

            GuestPreacher::create($request->all());

            return Result::Simple('Guest created', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Result::Simple($e->validator->errors(), 400);
        }
    }

    // Get guest by id
    public function getById($id)
    {
        $guest = GuestPreacher::where('id', $id)->first();

        return Result::WithResult($guest, 200);
    }

    // update guest
    public function update(Request $request)
    {
        try {
            if ($request->email != null) {
                $request->validate([
                    'id' => 'required|integer',
                    'name' => 'required',
                    'topic' => 'required|min:5',
                    'invitation_status' => 'required|integer',
                    'email' => 'required|email',
                    'phone' => 'required|min:10|max:10',
                    'church_from' => 'required',
                    'updated_by' => 'required|integer',
                ]);
            } else {
                $request->validate([
                    'id' => 'required|integer',
                    'name' => 'required',
                    'topic' => 'required|min:5',
                    'invitation_status' => 'required|integer',
                    'phone' => 'required|min:10|max:10',
                    'church_from' => 'required',
                    'updated_by' => 'required|integer',
                ]);
            }

            GuestPreacher::where('id', $request->id)->update($request->all());

            return Result::Simple('Guest preacher has been edited successfully', 204);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Result::Simple($e->validator->errors(), 404);
        }
    }

    public function delete($id)
    {

        $exists = GuestPreacher::where('id', $id)->get();

        if (count($exists) < 1) {
            return Result::Simple('Guest Preacher does not exist', 404);
        }

        GuestPreacher::where('id', $id)->delete();

        return Result::Simple("Successfully deleted guest preacher", 204);
    }
}
