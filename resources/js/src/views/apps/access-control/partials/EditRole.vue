<template>
  <div>
    <el-dialog
      :visible.sync="isEditRoleSidebarActive"
      :before-close="handleClose"
      title="Edit Role"
    >
      <template>
        <div
          class="justify-content-between align-items-center px-2 py-1"
        >
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
    selectedRole: {
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
      isEditRoleSidebarActive: true,
    };
  },
  created() {
    this.form.id = this.selectedRole.id;
    this.form.name = this.selectedRole.name;
    this.form.description = this.selectedRole.description;
  },
  methods: {
    handleClose(done) {
      this.$emit('close', true);
      done();
    },
    update() {
      const app = this;
      app.loading = true;
      const updateCurriculumSetupResource = new Resource('acl/roles/update');
      const param = app.form;
      updateCurriculumSetupResource.update(param.id, param)
        .then(response => {
          app.loading = false;
          app.$emit('update', response.roles);
          app.$message('Role Updated Successfully');
        });
    },
  },
};
</script>
