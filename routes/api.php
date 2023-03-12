<?php

use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\GuestPreacherController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Models\GuestPreacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get("/roles", [RoleController::class, 'index']);

Route::controller(UserController::class)->group(function () {

    Route::post("/login", "login");

    Route::group(['middleware' => ['auth:sanctum']], function () {

        Route::get("/users", 'index');

        Route::get("/users/{id}", "getUserById");

        Route::post("/users", 'store');

        Route::put("/users", 'edit');

        Route::post("/admin/users", 'store_admin');

        Route::delete("/users/delete", "delete");

        Route::put("/users/changePassword", "changePassword");

        Route::post("/logout", "logout");
    });
});

Route::controller(MeetingController::class)->group(function () {

    // Create meeting
    Route::post("/meetings", 'store');

    //Get all meetings
    Route::get("/meetings", "index");

    // Get meeting attendees
    Route::get("/meeting-attendees", "getMeetingAttendes");

    // Get one meeting
    Route::get("/meetings/{id}", "getById");

    // Set current meeting
    Route::post("/set-current-meeting/{id}", "setCurrentMeeting");

    // Get current meeting
    Route::get("/current-meeting", "getCurrent");

    // Edit meeting
    Route::put("/meetings", "edit");

    // Upload minutes
    Route::put("/meetings/minutes", "upload");

    // Download minutes
    Route::get("/download/minutes/{fileName}", "download");

    // Delete meeting
    Route::delete("/meetings/{id}", "delete");
});

Route::controller(AttendeeController::class)->group(function () {

    // Get all attendees
    Route::get("/attendees", "index");

    // Get attendee 
    Route::get("/attendee/{id}", "getAttendeeById");

    // Get attendees by meeting_id
    Route::get("/meeting-attendees/{id}", "getAttendeesByMeetingId");

    // Create meeting attendee
    Route::post("/attendees", "store");

    // Add user to attendees
    Route::post("/add/old-user/to-attendee", "addUserToAttendee");

    // Update 
    // Route::put("/attendees", "update");

    // Delete
    Route::delete("/attendees/{id}", "delete");
});

Route::controller(GuestPreacherController::class)->group(function () {

    // Get all guest preachers
    Route::get("/guest-preachers", "index");

    // Get guest by id
    Route::get("/guest-preacher/{id}", 'getById');

    // Create guest
    Route::post("/guest-preacher", "store");

    // Update guest
    Route::put("/guest-preacher", "update");

    //Delete guest

    Route::delete("/guest-preacher/{id}", "delete");
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
