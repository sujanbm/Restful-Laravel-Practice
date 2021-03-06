<?php

namespace App\Http\Controllers;

use App\Meeting;
use App\User;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    /**
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'meeting_id' => 'required',
            'user_id' => 'required',
        ]); 
        
        $meeting_id = $request->input('meeting_id');
        $user_id = $request->input('user_id');

        $meeting = Meeting::findOrFail($meeting_id);
        $user = User::findOrFail($user_id);


        $message = [
            'msg' => 'User already registered',
            'user' => $user,
            'meeting' => $meeting,
            'unregister' => [
                'href' => 'api/v1/registration/'. $meeting->id,
                'method' => 'DELETE'
            ]
        ];

        if ($meeting->users()->where('users.id', $user_id)->first()){
            return response()->json($message, 404);
        }

        $user->meeting()->attach();

        $response = [
            'msg' => 'User registered for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'unregister' => [
                'href' => 'api/v1/meeting/registration/'. $meeting->id,
                'method' => 'DELETE'
            ]
        ];

        return response()->json($response, 201);
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $meeting = Meeting::findOrFail($id);
        if (! $user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg'=>'User not found'], 404);
        }
        if (!$meeting->users()->where('users.id', $user->id)->first()){
            return response()->json(['msg'=>'User not registered for the meeting, delete unsuccessful'], 401);
        }
        $meeting->users()->detach($user->id);
        $response = [
            'msg' => 'User unregistered for meeting',
            'meeting' => $meeting,
            'user' => $user,
            'register' => [
                'href' => 'api/v1/meeting/registration',
                'method' => 'POST',
                'params' => 'user_id, meeting_id'
            ]
        ];

        return response()->json($response, 200);
    }
}
