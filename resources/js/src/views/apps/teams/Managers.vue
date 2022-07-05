<template>
  <div>
    <el-card>
      <div slot="header">
        <el-row>
          <el-col
            :md="12"
          >
            <h4>Manager List</h4>
          </el-col>
          <!-- <el-col
            :md="12"
          >
            <span class="pull-right">
              <el-button
                type="primary"
                size="small"
                icon="el-icon-plus"
                @click="isCreateTeamSidebarActive = true"
              > Add Team</el-button>
              <el-button
                type="success"
                size="small"
                icon="el-icon-user"
                @click="isCreateTeamMembersSidebarActive = true"
              > Add Team Members</el-button>
            </span>
          </el-col> -->
        </el-row>
      </div>
      <!-- table -->

      <v-client-table
        v-loading="loading"
        v-model="managers"
        :columns="columns"
        :options="options"
      >
        <!-- <div
          slot="domain"
          slot-scope="{row}"
        >
          <el-popover
            :content="(row.manager_domain) ? row.manager_domain.domain_names : 'Area Not Set'"
            placement="left"
            title="Coverage Area"
            width="500"
            trigger="hover">
            <el-button slot="reference" type="info">See Coverage Area</el-button>
          </el-popover>
        </div> -->
        <div
          slot="member_of_team.team.name"
          slot-scope="{row}"
        >
          {{ (row.member_of_team) ? row.member_of_team.team.name : 'Not added to a team' }}
        </div>
        <div
          slot="manager_domain.type"
          slot-scope="{row}"
        >
          {{ (row.manager_domain) ? row.manager_domain.type.toUpperCase() : 'No level set' }}
        </div>
        <div
          slot="action"
          slot-scope="props"
        >
          <el-button
            type="danger"
            size="small"
            round
            icon="el-icon-edit"
            @click="editThisRow(props.row)"
          >Set Coverage Area</el-button>
        </div>
      </v-client-table>
    </el-card>
    <el-dialog
      :visible.sync="setManagerDomain"
      :title="'Set Coverage Area for ' + selected_row.name"
    >
      <template>
        <div class="justify-content-between align-items-center px-2 py-1">
          <el-row v-loading="loading">
            <strong>Pick Manager's Role</strong><br>
            <el-select
              v-model="manager_type_index"
              placeholder="Select Role"
              style="width: 100%"
              @input="fetchSubordinates()"
            >
              <el-option
                v-for="(manager_type,index) in manager_types"
                :key="index"
                :value="index"
                :label="manager_type.name"
              />
            </el-select>
            <br>
            <!-- <div v-if="manager_type_index === 'nsm'">
              <label>Pick Coverage Domain (Multiple selection enabled)</label>
              <el-select
                v-model="manager_details.domain_values"
                placeholder="Select Country"
                style="width: 100%"
                multiple
                filterable
                collapse-tags
              >
                <el-option
                  v-for="(country,index) in countries"
                  :key="index"
                  :value="country.id + '|' + country.name"
                  :label="'(' + country.id + ') ' + country.name"
                />
              </el-select>
            </div>
            <div v-if="manager_type_index === 'rsm'">
              <label>Pick Manager's Coverage Region (Multiple selection enabled)</label>
              <el-select
                v-model="manager_details.domain_values"
                placeholder="Select States/Regions"
                style="width: 100%"
                multiple
                filterable
                collapse-tags
              >
                <el-option
                  v-for="(state,index) in states"
                  :key="index"
                  :value="state.id + '|' + state.name"
                  :label="'(' + state.id + ') ' + state.name"
                />
              </el-select>
            </div>
            <div v-if="manager_type_index === 'asm'">
              <label>Pick Manager's Coverage Area (Multiple selection enabled)</label>
              <el-select
                v-model="manager_details.domain_values"
                placeholder="Select Role"
                style="width: 100%"
                multiple
                filterable
                collapse-tags
              >
                <el-option
                  v-for="(lga,index) in lgas"
                  :key="index"
                  :value="lga.id + '|' + lga.name"
                  :label="'(' + lga.id + ') ' + lga.name + ', ' + lga.state.name"
                />
              </el-select>
            </div>
            <br> -->

            <div v-if="type === 'asm'">
              <label>Pick {{ selected_subordinate.toUpperCase() }} (Multiple selection enabled)</label>
              <el-select
                v-model="manager_details.reps_ids"
                placeholder="Select Reps"
                style="width: 100%"
                multiple
                filterable
                collapse-tags
              >
                <el-option
                  v-for="(team_rep, index) in team_reps"
                  :key="index"
                  :value="`${team_rep.id}`"
                  :label="'(' + team_rep.id + ') ' + team_rep.name"
                />
              </el-select>
              <br>
              <el-button
                :disabled="manager_details.reps_ids.length < 1"
                type="primary"
                @click="submit()"
              >
                Submit
              </el-button>
            </div>
            <br>
            <div v-if="type !=='' && type !== 'asm'">

              <el-alert
                v-if="downlinks.length < 1"
                :title="'There are no ' + selected_subordinate.toUpperCase() + 's for ' + selected_name"
                type="error"
                effect="dark"/>

              <div v-else>
                <label>Pick {{ selected_subordinate.toUpperCase() }} (Multiple selection enabled)</label>
                <el-select
                  v-model="selected_downlink_indexes"
                  placeholder="Select"
                  style="width: 100%"
                  multiple
                  filterable
                  collapse-tags
                  @input="setDownlinkReps()"
                >
                  <!-- <el-option
                    v-for="(downlink, index) in downlinks"
                    :key="index"
                    :value="index"
                    :label="'(' + downlink.type.toUpperCase() + ') ' + downlink.user.name"
                    :disabled="downlink.user.id === selected_row.id"
                  /> -->
                  <el-option
                    v-for="(downlink, index) in downlinks"
                    :key="index"
                    :value="index"
                    :label="'(' + downlink.type.toUpperCase() + ') ' + downlink.user.name"
                  />
                </el-select>
              </div>
              <br>
              <el-button
                :disabled="downlinks.length < 1"
                type="primary"
                @click="submit()"
              >
                Submit
              </el-button>
            </div>
          </el-row>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import Resource from '@/api/resource';
import CreateTeam from './partials/AddTeam.vue';
import CreateTeamMembers from './partials/AddTeamMembers.vue';
import EditTeam from './partials/EditTeam.vue';

export default {
  components: {
    CreateTeam, EditTeam, CreateTeamMembers,
  },
  data() {
    return {
      loading: false,
      isCreateTeamSidebarActive: false,
      isEditTeamSidebarActive: false,
      isCreateTeamMembersSidebarActive: false,
      pageLength: 10,
      dir: false,

      columns: [
        'name',
        // 'email',
        // 'phone',
        'member_of_team.team.name',
        'manager_domain.type',
        // 'domain',
        'action',
      ],

      options: {
        headings: {
          domain: 'Coverage Area',
          'member_of_team.team.name': 'Team',
          'manager_domain.type': 'Managerial Level',
          action: '',

          // id: 'S/N',
        },
        // pagination: {
        //   dropdown: true,
        //   chunk: 10,
        // },
        perPage: 10,
        filterByColumn: true,
        texts: {
          filter: 'Search:',
        },
        sortable: [
          // 'name',
          'name',
          'member_of_team.team.name',
          // 'description',
        ],
        // filterable: false,
        filterable: [
          // 'name',
          'name',
          'member_of_team.team.name',
          // 'description',
        ],
      },
      managers: [],
      searchTerm: '',
      editable_row: '',
      selected_row: '',
      setManagerDomain: false,
      teams: [],
      manager_types: [],
      manager_type_index: '',
      manager_details: {
        type: '',
        reps_ids: [],
        downlink_ids: [],
        team_id: '',
        user_id: '',
        report_to: '',
      },
      downlinks: [],
      selected_name: '',
      type: '',
      selected_subordinate: '',
      team_reps: [],
      selected_downlink_indexes: [],
    };
  },
  created() {
    this.fetchManagers();
    // this.fetchTeams();
    this.fetchManagerTypes();
  },
  methods: {
    fetchManagerTypes() {
      const app = this;
      const necessaryParams = new Resource('teams/fetch-managers-types');
      necessaryParams.list().then((response) => {
        app.manager_types = response.manager_types;
      });
    },
    fetchTeams() {
      const app = this;
      // app.loading = true;
      const fetchTeamsResource = new Resource('teams');
      fetchTeamsResource.list()
        .then(response => {
          app.teams = response.teams;
          // app.loading = false;
        });
    },
    fetchManagers() {
      const app = this;
      app.loading = true;
      const fetchManagersResource = new Resource('teams/managers');
      fetchManagersResource.list()
        .then(response => {
          app.managers = response.managers;
          app.loading = false;
        });
    },
    fetchSubordinates() {
      const app = this;
      const selected_manager = app.selected_row;
      if (selected_manager.member_of_team) {
        const team_id = selected_manager.member_of_team.team_id;
        const manager_type = app.manager_type_index;
        app.downlinks = app.manager_types[manager_type].downlinks;
        app.type = app.manager_types[manager_type].slug;
        app.selected_subordinate = app.manager_types[manager_type].subordinate;
        app.selected_name = app.manager_types[manager_type].name;

        app.manager_details.type = app.type;
        app.manager_details.report_to = app.manager_types[manager_type].report_to;
        if (app.type === 'asm') {
          app.fetchTeamReps(team_id);
        }
      } else {
        app.$alert('Kindly add ' + selected_manager.name + ' to a team before continuing');
      }
    },
    fetchTeamReps(team_id) {
      const app = this;
      const fetchTeamRepsResource = new Resource('teams/fetch-reps');
      const param = { team_id };
      fetchTeamRepsResource.list(param)
        .then(response => {
          app.team_reps = response.team_reps;
        });
    },
    handleClose(tag) {
      this.manager_details.domain_values.splice(this.manager_details.domain_values.indexOf(tag), 1);
    },
    // updateTable() {
    //   const app = this;
    //   app.fetchTeams();
    //   app.isCreateTeamSidebarActive = false;
    //   app.isCreateTeamMembersSidebarActive = false;
    //   app.isEditTeamSidebarActive = false;
    // },
    editThisRow(selectedRow) {
      // console.log(props)
      const app = this;
      if (selectedRow.member_of_team) {
        app.selected_row = selectedRow;

        app.manager_details.team_id = selectedRow.member_of_team.team_id;
        app.manager_details.user_id = selectedRow.id;

        if (selectedRow.manager_domain) {
          app.manager_details.reps_ids = selectedRow.manager_domain.reps_ids.split('~');
        }
        app.setManagerDomain = true;
      } else {
        app.$alert('Kindly add ' + selectedRow.name + ' to a team before continuing');
      }
      // const editableRow = selected_row;
    },
    setDownlinkReps() {
      const app = this;
      const downlink_indexes = app.selected_downlink_indexes;
      const downlinks = [];
      let reps_ids = '';
      downlink_indexes.forEach(index => {
        const downlink = app.downlinks[index];
        downlinks.push(downlink.id);
        reps_ids += '~' + downlink.reps_ids;
      });
      app.manager_details.downlink_ids = downlinks;
      reps_ids = reps_ids.substring(1); // remove the first character of string which is the ~ character
      app.manager_details.reps_ids = reps_ids.split('~'); // cast to array
    },
    submit() {
      const app = this;
      const param = app.manager_details;
      const submitManagerDomain = new Resource('teams/manager/set-coverage-domain');
      submitManagerDomain.store(param).then(() => {
        app.resetParams();
        app.setManagerDomain = false;
        app.fetchManagers();
      });
    },
    resetParams() {
      this.manager_details = {
        type: '',
        reps_ids: [],
        downlink_ids: [],
        team_id: '',
        user_id: '',
        report_to: '',
      };
      this.manager_type_index = '';
    },
  },
};
</script>
