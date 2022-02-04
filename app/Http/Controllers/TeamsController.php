<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\TeamMember;

class TeamsController extends Controller
{
    public function index()
    {
        $teams = Team::with('members.user')->orderBy('name')->get();
        return response()->json(compact('teams'), 200);
    }

    public function store(Request $request)
    {
        $name = $request->name;
        $display_name = ucwords($name);
        $description = $request->description;
        $team = Team::where('name', $name)->first();
        if (!$team) {
            $team = new Team();
            $team->name = $name;
            $team->display_name = $display_name;
            $team->description = $description;
            $team->save();
        }
        return $this->index();
    }

    public function update(Request $request, Team $team)
    {
        $name = $request->name;
        $display_name = ucwords($name);
        $description = $request->description;

        $team->name = $name;
        $team->display_name = $display_name;
        $team->description = $description;
        $team->save();
        return $this->index();
    }

    public function destroy(Request $request, Team $team)
    {
        $team->members()->delete();
        $team->delete();
        return $this->index();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addMembers(Request $request)
    {
        $user_ids = json_decode(json_encode($request->user_ids));
        $team_id = $request->team_id;
        foreach ($user_ids as $user_id) {
            $team_member = TeamMember::where(['team_id' => $team_id, 'user_id' => $user_id])->first();
            if (!$team_member) {
                $team_member = new TeamMember();
                $team_member->user_id = $user_id;
                $team_member->team_id = $team_id;
                $team_member->save();
            }
        }
        return $this->teamMembers($request);
    }

    public function fetchTeamMembers(Request $request)
    {
        $team_id = $request->team_id;
        $team_members = TeamMember::with('user', 'team')->where('team_id', $team_id)->get();
        return response()->json(compact('team_members'), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function removeMember(Request $request, TeamMember $team_member)
    {
        $request->team_id = $team_member->team_id;
        $team_member->delete();
        return $this->teamMembers($request);
    }

    public function createTeamLead(Request $request, TeamMember $team_member)
    {
        $request->team_id = $team_member->team_id;
        $team_members = TeamMember::with('user', 'team')->where('team_id', $team_member->team_id)->get();
        foreach ($team_members as $member) {
            $member->is_lead = '0';
            $member->save();
        }
        $team_member->is_lead = '1';
        $team_member->save();
        return $this->teamMembers($request);
    }
}
