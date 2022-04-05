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
        <div
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
        </div>
        <div
          slot="level"
          slot-scope="{row}"
        >
          {{ displayManagementLevel(row) }}
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
              v-model="selected_role"
              placeholder="Select Role"
              style="width: 100%"
              @input="checkForManagers()"
            >
              <el-option
                v-for="(role,index) in roles"
                :key="index"
                :value="role.value"
                :label="role.name"
              />
            </el-select>
            <br>
            <div v-if="selected_role === 'nsm'">
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
            <div v-if="selected_role === 'rsm'">
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
            <div v-if="selected_role === 'asm'">
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
            <br>
            <el-tag
              v-for="tag in manager_details.domain_values"
              :key="tag"
              :disable-transitions="false"
              type="danger"
              closable
              @close="handleClose(tag)"
            >
              {{ tag }}
            </el-tag>
            <br>
            <br>
            <br>
            <el-button
              type="primary"
              @click="submit()"
            >
              Submit
            </el-button>
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
        'email',
        'phone',
        'level',
        'domain',
        'action',
      ],

      options: {
        headings: {
          domain: 'Coverage Area',
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
          'level',
          // 'description',
        ],
        // filterable: false,
        filterable: [
          // 'name',
          'name',
          'level',
          // 'description',
        ],
      },
      managers: [],
      searchTerm: '',
      editable_row: '',
      selected_row: '',
      setManagerDomain: false,
      states: [],
      lgas: [],
      countries: [{
        id: 160,
        name: 'Nigeria',
      }],
      roles: [
        {
          value: 'asm',
          name: 'Area Sales Manager',
        },
        {
          value: 'rsm',
          name: 'Regional Sales Manager',
        },
        {
          value: 'nsm',
          name: 'National Sales Manager',
        },
      ],
      selected_role: '',
      manager_details: {
        type: '',
        domain_values: [],
      },
    };
  },
  created() {
    this.fetchManagers();
    this.fetchNecessaryParams();
  },
  methods: {
    fetchNecessaryParams() {
      const app = this;
      const necessaryParams = new Resource('fetch-necessary-params');
      necessaryParams.list().then((response) => {
        app.states = response.params.states;
        app.lgas = response.params.lgas;
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
    checkForManagers() {
      const app = this;
      const role = app.selected_role;

      app.manager_details.domain_values = (app.selected_row.manager_domain) ? ((app.selected_row.manager_domain.domain_full_details !== null) ? app.selected_row.manager_domain.domain_full_details.split('~') : []) : [];
      if (role === 'nsm') {
        app.manager_details.type = 'country';
      }
      if (role === 'rsm') {
        // we pick the states
        app.manager_details.type = 'state';
      } else if (role === 'asm') {
        // we pick the lgas
        app.manager_details.type = 'lga';
      }
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
      app.selected_row = selectedRow;
      const domain = (selectedRow.manager_domain) ? selectedRow.manager_domain.domain : '';
      app.selected_role = '';
      if (domain === 'country') {
        app.selected_role = 'nsm';
      } else if (domain === 'state') {
        app.selected_role = 'rsm';
      } else if (domain === 'lga') {
        app.selected_role = 'asm';
      }
      app.checkForManagers();
      // const editableRow = selected_row;
      app.setManagerDomain = true;
    },
    displayManagementLevel(row) {
      const domain = (row.manager_domain) ? row.manager_domain.domain : '';
      let level = '';
      if (domain === 'country') {
        level = 'National Sales Manager (NSM)';
      } else if (domain === 'state') {
        level = 'Regional Sales Manager (RSM)';
      } else if (domain === 'lga') {
        level = 'Area Sales Manager (ASM)';
      }
      return level;
    },
    submit() {
      const app = this;
      const param = {
        user_id: app.selected_row.id,
        domain: app.manager_details.type,
        domain_values: app.manager_details.domain_values,

      };
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
        domain_values: [],
      };
      this.selected_role = '';
    },
  },
};
</script>
