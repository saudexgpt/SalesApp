<?php

namespace App\Http\Controllers;

use App\Models\ManagerDomain;
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

    public function fetchManagers()
    {
        $userQuery = User::query();

        $userQuery->whereHas('roles', function ($q) {
            $q->where('name', 'manager');
        });
        $managers = $userQuery->with('managerDomain')->get();


        return response()->json(compact('managers'), 200);
    }

    public function setCoverageDomain(Request $request)
    {
        $user_id = $request->user_id;
        $domain_values_array  = json_decode(json_encode($request->domain_values));
        $domain_ids_array = [];
        $domain_names_array = [];
        foreach ($domain_values_array as $domain_value) {
            $domain_value_array = explode('|', $domain_value);
            $domain_ids_array[] = $domain_value_array[0];
            $domain_names_array[] = $domain_value_array[1];
        }
        $domain_ids = implode('~', $domain_ids_array);
        $domain_names = implode(', ', $domain_names_array);
        $domain_full_details = implode('~', $domain_values_array);
        $manager_domain = ManagerDomain::where('user_id', $user_id)->first();
        if (!$manager_domain) {
            $manager_domain = new ManagerDomain();
        }
        $manager_domain->user_id = $user_id;
        $manager_domain->domain = $request->domain;
        $manager_domain->domain_ids = $domain_ids;
        $manager_domain->domain_names = $domain_names;
        $manager_domain->domain_full_details = $domain_full_details;
        $manager_domain->save();

        return response()->json([], 200);
    }

    // public function fetchManagers()
    // {
    //     $userQuery = User::query();

    //     $userQuery->whereHas('roles', function ($q) {
    //         $q->where('name', 'manager');
    //     });
    //     $managers = $userQuery->with('assignedManagerDomains.managerType', 'assignedManagerDomains.managerDomain.country', 'assignedManagerDomains.managerDomain.state', 'assignedManagerDomains.managerDomain.lga.state')->get();


    //     return response()->json(compact('managers'), 200);
    // }

    // public function fetchManagerTypes()
    // {
    //     $manager_types = ManagerType::with([
    //         'managerDomains.country', 'managerDomains.state', 'managerDomains.lga.state'
    //     ])->get();


    //     return response()->json(compact('manager_types'), 200);
    // }

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
