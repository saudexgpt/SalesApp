<?php

namespace App\Http\Controllers;

use App\Models\ManagerDomain;
use App\Models\ManagerType;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

class TeamsController extends Controller
{
    public function index()
    {
        $teams = Team::with('members.user')->orderBy('name')->get();
        return response()->json(compact('teams'), 200);
    }
    public function fetchTeamReps(Request $request)
    {
        $team_id = $request->team_id;
        $userQuery = User::query();

        $userQuery->whereHas('roles', function ($q) {
            $q->where('name', 'sales_rep');
        });
        $team_reps = $userQuery->join('team_members', 'team_members.user_id', 'users.id')
            ->where('team_id', $team_id)->get();
        return response()->json(compact('team_reps'), 200);
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
            $team_member = TeamMember::where([/*'team_id' => $team_id, */'user_id' => $user_id])->first();
            if (!$team_member) {
                $team_member = new TeamMember();
            }

            $team_member->user_id = $user_id;
            $team_member->team_id = $team_id;
            $team_member->save();
        }
        // return $this->teamMembers($request);
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
        // $request->team_id = $team_member->team_id;
        $team_member->delete();
        // return $this->teamMembers($request);
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
        // return $this->teamMembers($request);
    }

    public function fetchManagers()
    {
        $userQuery = User::query();

        $userQuery->whereHas('roles', function ($q) {
            $q->where('name', 'manager');
        });
        $managers = $userQuery->with('memberOfTeam.team', 'managerDomain')->get();


        return response()->json(compact('managers'), 200);
    }

    public function setCoverageDomain(Request $request)
    {
        $user_id = $request->user_id;
        $reps_ids_array  = array_unique(json_decode(json_encode($request->reps_ids)));
        $downlink_ids_array  = array_unique(json_decode(json_encode($request->downlink_ids)));
        $reps_ids = implode('~', $reps_ids_array);
        $downlink_ids = implode('~', $downlink_ids_array);
        $type = $request->type;
        $team_id = $request->team_id;
        $report_to = $request->report_to;
        $manager_domain = ManagerDomain::where('user_id', $user_id)->first();
        if (!$manager_domain) {
            $manager_domain = new ManagerDomain();
        }
        $manager_domain->user_id = $user_id;
        $manager_domain->reps_ids = $reps_ids;
        $manager_domain->downlink_ids = $downlink_ids;
        $manager_domain->type = $type;
        $manager_domain->team_id = $team_id;
        $manager_domain->report_to = $report_to;
        $manager_domain->save();
        $this->populateUplinkReps($manager_domain);

        return response()->json([], 200);
    }
    private function populateUplinkReps($downlink_detail)
    {
        $uplink_detail = ManagerDomain::where('downlink_ids', 'LIKE', '%' . $downlink_detail->id . '%')
            ->orWhere('downlink_ids', 'LIKE', '%' . $downlink_detail->id . '~%')
            ->orWhere('downlink_ids', 'LIKE', '%~' . $downlink_detail->id . '~%')
            ->orWhere('downlink_ids', 'LIKE', '%~' . $downlink_detail->id . '%')
            ->first();
        if ($uplink_detail) {
            $updated_reps = addSingleElementToString($uplink_detail->reps_ids, $downlink_detail->reps_ids);
            $uplink_detail->reps_ids = $updated_reps;
            $uplink_detail->save();
            $this->populateUplinkReps($uplink_detail);
        }
    }

    public function fetchManagerTypes()
    {
        $manager_types = ManagerType::with('downlinks.user')->get();


        return response()->json(compact('manager_types'), 200);
    }

    // public function setCoverageDomain(Request $request)
    // {
    //     $user_id = $request->user_id;
    //     $type_id = $request->type_id;
    //     $domain_values_array  = json_decode(json_encode($request->domain_values));
    //     // $domain_ids_array = [];
    //     // $domain_names_array = [];
    //     foreach ($domain_values_array as $domain_value) {
    //         $domain_value_array = explode('|', $domain_value);
    //         $domain_id = $domain_value_array[0];
    //         $domain_name = $domain_value_array[1];
    //         $manager_domain = AssignedManagerDomain::where('manager_domain_id', $domain_id)->first();
    //         if (!$manager_domain) {
    //             $manager_domain = new AssignedManagerDomain();
    //         }
    //         $manager_domain->user_id = $user_id;
    //         $manager_domain->manager_type_id = $type_id;
    //         $manager_domain->manager_domain_id = $domain_id;
    //         $manager_domain->domain_name = $domain_name;
    //         $manager_domain->save();
    //     }

    //     return response()->json([], 200);
    // }
}
