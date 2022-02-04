<template>
  <el-dialog
    :visible.sync="isCreateRoleSidebarActive"
    :before-close="handleClose"
    title="Create Role"
  >
    <template>
      <div class="justify-content-between align-items-center px-2 py-1">
        <el-row v-loading="loading">
          <strong>Role Name</strong><br>
          <el-input
            v-model="form.name"
            placeholder="Eg: Manager"/>
          <br>
          <strong>Role Description</strong><br>
          <el-input
            v-model="form.description"
            placeholder="Eg: The Manager of the company"
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
      isCreateRoleSidebarActive: true,
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
      const saveRoleResource = new Resource('acl/roles/save');
      const param = app.form;
      saveRoleResource.store(param)
        .then(response => {
          app.loading = false;
          app.$emit('save', response.roles);
          app.$message('Role Added Successfully');
        }).catch(error => {
          app.error = true;
          app.$message(error.response.data.message);
          app.loading = false;
        });
    },
  },
};
</script>
