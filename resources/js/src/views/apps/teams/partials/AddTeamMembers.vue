<template>
  <el-dialog
    :visible.sync="isCreateTeamSidebarActive"
    :before-close="handleClose"
    title="Add Team Members"
  >
    <template>
      <div class="justify-content-between align-items-center px-2 py-1">
        <el-row v-loading="loading">
          <strong>Select Team</strong><br>
          <el-select v-model="form.team_id" style="width: 100%" filterable>
            <el-option
              v-for="(team, team_idex) in teams"
              :key="team_idex"
              :label="team.display_name"
              :value="team.id"/>
          </el-select>
          <br>
          <strong>Select Team Members</strong><br>
          <el-select v-model="form.user_ids" style="width: 100%" multiple filterable>
            <el-option
              v-for="(user, user_idex) in users"
              :key="user_idex"
              :label="user.name"
              :value="user.id"/>
          </el-select>
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
</template>

<script>
import Resource from '@/api/resource';

export default {
  props: {
    teams: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      users: [],
      form: {
        team_id: '',
        user_ids: [],
      },
      loading: false,
      error: false,
      error_message: '',
      isCreateTeamSidebarActive: true,
    };
  },
  created() {
    this.fetchUsers();
  },
  methods: {
    handleClose(done) {
      this.$emit('close', true);
      done();
    },
    fetchUsers() {
      const app = this;
      app.loading = true;
      const allUsersResource = new Resource('users/all');
      allUsersResource.list()
        .then(response => {
          app.loading = false;
          app.users = response.users;
        }).catch(error => {
          app.error = true;
          app.$message(error.response.data.message);
          app.loading = false;
        });
    },
    submit() {
      const app = this;
      app.loading = true;
      const saveTeamResource = new Resource('teams/add-members');
      const param = app.form;
      saveTeamResource.store(param)
        .then(response => {
          app.loading = false;
          app.$emit('save', response.team_members);
          app.$message('Team Members Added Successfully');
        }).catch(error => {
          app.error = true;
          app.$message(error.response.data.message);
          app.loading = false;
        });
    },
  },
};
</script>
