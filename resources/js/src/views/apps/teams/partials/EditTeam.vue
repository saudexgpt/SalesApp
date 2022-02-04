<template>
  <div>
    <el-dialog
      :visible.sync="isEditTeamSidebarActive"
      :before-close="handleClose"
      title="Edit Team"
    >
      <template>
        <div
          class="justify-content-between align-items-center px-2 py-1"
        >
          <el-row v-loading="loading">
            <strong>Team Name</strong><br>
            <el-input
              v-model="form.name"
              placeholder="Eg: Manager"/>
            <br>
            <strong>Team Description</strong><br>
            <el-input
              v-model="form.description"
              placeholder="Eg: The Manager of the company"
            />
            <br>
            <el-button
              type="primary"
              @click="update()"
            >
              Update
            </el-button>
          </el-row>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import Resource from '@/api/resource';

export default {
  components: {
  },
  props: {
    selectedTeam: {
      type: Object,
      default: () => (null),
    },
  },
  data() {
    return {
      form: {
        id: '',
        name: '',
        description: '',
      },
      loading: false,
      isEditTeamSidebarActive: true,
    };
  },
  created() {
    this.form.id = this.selectedTeam.id;
    this.form.name = this.selectedTeam.name;
    this.form.description = this.selectedTeam.description;
  },
  methods: {
    handleClose(done) {
      this.$emit('close', true);
      done();
    },
    update() {
      const app = this;
      app.loading = true;
      const updateTeamResource = new Resource('teams/update');
      const param = app.form;
      updateTeamResource.update(param.id, param)
        .then(response => {
          app.loading = false;
          app.$emit('update', response.teams);
          app.$message('Team Updated Successfully');
        });
    },
  },
};
</script>
