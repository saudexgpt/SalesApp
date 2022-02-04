<template>
  <el-dialog
    :visible.sync="isCreateTeamSidebarActive"
    :before-close="handleClose"
    title="Create Team"
  >
    <template>
      <div class="justify-content-between align-items-center px-2 py-1">
        <el-row v-loading="loading">
          <strong>Team Name</strong><br>
          <el-input
            v-model="form.name"
            placeholder="Enter Team Name"/>
          <br>
          <strong>Team Description</strong><br>
          <el-input
            v-model="form.description"
            placeholder="Enter Team Description"
          />
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
  components: {
  },
  data() {
    return {
      form: {
        name: '',
        description: '',
      },
      loading: false,
      error: false,
      error_message: '',
      isCreateTeamSidebarActive: true,
    };
  },
  methods: {
    handleClose(done) {
      this.$emit('close', true);
      done();
    },
    submit() {
      const app = this;
      app.loading = true;
      const saveTeamResource = new Resource('teams/store');
      const param = app.form;
      saveTeamResource.store(param)
        .then(response => {
          app.loading = false;
          app.$emit('save', response.teams);
          app.$message('Team Added Successfully');
        }).catch(error => {
          app.error = true;
          app.$message(error.response.data.message);
          app.loading = false;
        });
    },
  },
};
</script>
