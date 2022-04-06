<template>
  <el-card>
    <div slot="header">
      <h4>Members {{ (team_members.length > 0) ? ' of ' + team_members[0].team.display_name : '' }}</h4>
    </div>
    <v-client-table
      v-loading="loading"
      v-model="team_members"
      :columns="columns"
      :options="options"
    >
      <div
        slot="action"
        slot-scope="props"
      >
        <el-button
          round
          size="small"
          type="success"
          @click="makeTeamLead(props.row)"
        >Make Leader
        </el-button>
        <el-button
          round
          type="danger"
          size="small"
          icon="el-icon-delete"
          @click="removeMember(props.row)"
        > Remove</el-button>
      </div>
    </v-client-table>
  </el-card>
</template>

<script>
import Resource from '@/api/resource';

export default {
  data() {
    return {
      loading: false,
      team_members: [],
      columns: [
        // 'name',
        'user.name',
        'user.phone',
        'user.email',
        'is_lead',
        'action',
      ],

      options: {
        headings: {
          'user.name': 'Member Name',
          'user.phone': 'Phone',
          'user.email': 'Email',
          is_lead: 'Is Team Lead',
          action: '',

          // id: 'S/N',
        },
        // pagination: {
        //   dropdown: true,
        //   chunk: 10,
        // },
        perPage: 10,
        // filterByColumn: true,
        texts: {
          filter: 'Search:',
        },
        sortable: [
          // 'name',
          'user.name',
          // 'description',
        ],
        // filterable: false,
        filterable: [
          // 'name',
          'user.name',
          // 'description',
        ],
      },
    };
  },
  created() {
    this.fetchMembers();
  },
  methods: {
    fetchMembers() {
      const app = this;
      const param = { team_id: app.$route.params.team_id };
      const teamMembersResource = new Resource('teams/members');
      app.loading = true;
      teamMembersResource.list(param)
        .then(response => {
          app.team_members = response.team_members;
          app.loading = false;
        }).catch((error) => {
          app.loading = false;
          console.log(error);
        });
    },
    makeTeamLead(member) {
      const app = this;
      const param = { team_id: member.team_id };
      const teamMembersResource = new Resource('teams/create-team-lead');
      // app.loading = true;
      teamMembersResource.update(member.id, param)
        .then(response => {
          app.team_members = response.team_members;
          // app.loading = false;
        }).catch((error) => {
          // app.loading = false;
          console.log(error);
        });
    },
    removeMember(member) {
      const app = this;
      const param = { team_id: member.team_id };
      if (confirm('Remove member from this team? You can always add again')) {
        const teamMembersResource = new Resource('teams/remove-member');
        // app.loading = true;
        teamMembersResource.destroy(member.id, param)
          .then(response => {
            app.fetchMembers();
          // app.loading = false;
          }).catch((error) => {
          // app.loading = false;
            console.log(error);
          });
      }
    },

  },
};
</script>
