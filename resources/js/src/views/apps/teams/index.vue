<template>
  <div>
    <el-card>
      <div slot="header">
        <el-row>
          <el-col
            :md="12"
          >
            <h4>Available Teams</h4>
          </el-col>
          <el-col
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
          </el-col>
        </el-row>
      </div>
      <!-- table -->

      <v-client-table
        v-loading="loading"
        v-model="teams"
        :columns="columns"
        :options="options"
      >
        <div
          slot="action"
          slot-scope="props"
        >
          <el-button
            type="warning"
            size="small"
            round
            icon="el-icon-edit"
            @click="editThisRow(props.row)"
          >Edit</el-button>
          <router-link
            :to="'team/members/' + props.row.id"
          >
            <el-button
              round
              type="primary"
              size="small"
              icon="el-icon-user"
            >View Members</el-button>
          </router-link>
        </div>
      </v-client-table>
    </el-card>
    <create-team
      v-if="isCreateTeamSidebarActive"
      @save="updateTable"
      @close="isCreateTeamSidebarActive = false"
    />
    <create-team-members
      v-if="isCreateTeamMembersSidebarActive"
      :teams="teams"
      @save="updateTable"
      @close="isCreateTeamMembersSidebarActive = false"
    />
    <edit-team
      v-if="isEditTeamSidebarActive"
      :selected-team="editable_row"
      @update="updateTable"
      @close="isEditTeamSidebarActive = false"
    />
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
        // 'name',
        'display_name',
        'description',
        'action',
      ],

      options: {
        headings: {
          display_name: 'Team',
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
          'display_name',
          // 'description',
        ],
        // filterable: false,
        filterable: [
          // 'name',
          'display_name',
          // 'description',
        ],
      },
      teams: [],
      searchTerm: '',
      editable_row: '',
      selected_row_index: '',
    };
  },
  created() {
    this.fetchTeams();
  },
  methods: {
    fetchTeams() {
      const app = this;
      app.loading = true;
      const fetchTeamsResource = new Resource('teams');
      fetchTeamsResource.list()
        .then(response => {
          app.teams = response.teams;
          app.loading = false;
        });
    },
    updateTable() {
      const app = this;
      app.fetchTeams();
      app.isCreateTeamSidebarActive = false;
      app.isCreateTeamMembersSidebarActive = false;
      app.isEditTeamSidebarActive = false;
    },
    editThisRow(selectedRow) {
      // console.log(props)
      const app = this;
      // const editableRow = selected_row;
      app.editable_row = selectedRow;
      app.isEditTeamSidebarActive = true;
    },
  },
};
</script>
