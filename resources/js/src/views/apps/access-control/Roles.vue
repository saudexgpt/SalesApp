<template>
  <el-tabs type="border-card">
    <el-tab-pane label="Manage Roles">
      <div>
        <el-row>
          <el-col
            :lg="16"
            :md="16"
            :sm="16"
            :xs="16"
          >
            <h4>Roles</h4>
          </el-col>
          <el-col
            :lg="8"
            :md="8"
            :sm="8"
            :xs="8"
          >
            <span class="pull-right">
              <el-button
                type="primary"
                @click="isCreateRoleSidebarActive = true"
              >
                <feather-icon
                  icon="PlusIcon"
                />
              </el-button>
            </span>
          </el-col>
        </el-row>
      </div>
      <v-client-table
        v-loading="loading"
        v-model="roles"
        :columns="columns"
        :options="options"
      >
        <div
          slot="level_groups"
          slot-scope="props"
        >
          <span
            v-for="(level_group, level_index) in props.row.level_groups"
            :key="level_index"
          >
            <small>{{ level_index + 1 + ') ' + level_group.name }}<br></small>
          </span>
        </div>
        <!-- <div
          slot="assigned_permissions"
          slot-scope="props"
        >
          <span
            v-for="(permission, perm_index) in props.row.permissions"
            :key="perm_index"
          >
            <small>{{ perm_index + 1 + ') ' + permission.display_name }}<br></small>
          </span>
        </div> -->
        <div
          slot="action"
          slot-scope="props"
        >
          <el-button
            type="warning"
            class="rounded-circle"
            @click="editThisRow(props.row)"
          >
            <feather-icon icon="EditIcon" />
          </el-button>
        </div>
      </v-client-table>
      <create-role
        v-if="isCreateRoleSidebarActive"
        @save="updateTable"
        @close="isCreateRoleSidebarActive = false"
      />
      <edit-role
        v-if="isEditRoleSidebarActive"
        :selected-role="editable_row"
        @update="updateEditedTableRow"
        @close="isEditRoleSidebarActive = false"
      />
    </el-tab-pane>
    <el-tab-pane label="Assign Role Permission">
      <aside>
        <el-row :gutter="5">
          <el-col
            :lg="12"
            :md="12"
            :sm="12"
            :xs="12"
          >
            <small>Select Role</small>
            <el-select
              v-model="selected_role_index"
              filterable
              style="width: 100%"
              @input="setPermissions()"
            >
              <!-- <el-option
              v-for="(role, index) in roles"
              :key="index"
              :value="index"
              :disabled="role.name === 'admin'"
              :label="role.display_name"
            /> -->
              <el-option
                v-for="(role, index) in roles"
                :key="index"
                :value="index"
                :label="role.display_name"
              />
            </el-select>
          </el-col>
        <!-- <el-col
          :lg="12"
          :md="12"
          :sm="12"
          :xs="12"
        >
          <small>Select relevant permissions to assign to selected role</small>
          <el-select
            v-model="new_permissions"
            multiple
            filterable
            collapse-tags
            style="width: 100%"
            @change="assignPermissions()"
          >
            <el-option
              v-for="(permission, index) in permissions"
              :key="index"
              :value="permission.id"
              :label="permission.display_name"
            />
          </el-select>
        </el-col> -->
        </el-row>
      </aside>
      <el-row :gutter="5">
        <h3>Avaliable Permissions</h3>
        <el-checkbox-group v-model="new_permissions" size="small">
          <el-checkbox
            v-for="(permission, index) in permissions"
            :key="index"
            :label="permission.id"
            border>
            {{ permission.display_name }}
          </el-checkbox>
        </el-checkbox-group>
      </el-row>
      <el-row :gutter="5">
        <el-button type="success" @click="assignPermissions()">Submit</el-button>
      </el-row>
    </el-tab-pane>
  </el-tabs>
</template>

<script>
// import { VueGoodTable } from 'vue-good-table'
import Resource from '@/api/resource';
import CreateRole from './partials/AddRole.vue';
import EditRole from './partials/EditRole.vue';

export default {
  components: {
    // VueGoodTable,
    CreateRole,
    EditRole,
  },
  data() {
    return {
      loading: false,
      isCreateRoleSidebarActive: false,
      isEditRoleSidebarActive: false,
      pageLength: 10,

      columns: [
        // 'name',
        'display_name',
        'description',
        // 'assigned_permissions',
        // 'action',
      ],

      options: {
        headings: {
          display_name: 'Role',
          // assigned_permissions: 'Assigned Permissions',
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
          'display_name',
          'description',
        ],
        // filterable: false,
        filterable: [
          // 'name',
          'display_name',
          'description',
        ],
      },
      roles: [],
      permissions: [],
      searchTerm: '',
      editable_row: '',
      selected_row_index: '',
      selected_role_index: '',
      new_permissions: [],
    };
  },
  created() {
    this.fetchPermissions();
    this.fetchRoles();
  },
  methods: {
    fetchRoles() {
      const app = this;
      app.loading = true;
      const fetchCurriculumSetupResource = new Resource('acl/roles/index');
      fetchCurriculumSetupResource.list()
        .then(response => {
          app.roles = response.roles;
          app.loading = false;
        });
    },
    fetchPermissions() {
      const app = this;
      const fetchCurriculumSetupResource = new Resource('acl/permissions/index');
      fetchCurriculumSetupResource.list()
        .then(response => {
          app.permissions = response.permissions;
        });
    },
    updateTable(roles) {
      const app = this;
      app.roles = roles;
      app.isCreateRoleSidebarActive = false;
    },
    editThisRow(selectedRow) {
      // console.log(props)
      const app = this;
      // const editableRow = selected_row;
      app.editable_row = selectedRow;
      app.isEditRoleSidebarActive = true;
    },
    updateEditedTableRow() {
      const app = this;
      app.fetchRoles();
      app.isEditRoleSidebarActive = false;
    },
    setPermissions() {
      const app = this;
      const roleIndex = app.selected_role_index;
      const rolePermissions = app.roles[roleIndex].permissions;
      const new_permissions = [];
      rolePermissions.forEach(permission => {
        new_permissions.push(permission.id);
      });
      app.new_permissions = new_permissions;
    },
    assignPermissions() {
      const app = this;
      const roleId = app.roles[app.selected_role_index].id;
      const fetchCurriculumSetupResource = new Resource('acl/permissions/assign-role');
      const param = { role_id: roleId, permissions: app.new_permissions };
      fetchCurriculumSetupResource.store(param)
        .then(response => {
          app.roles[app.selected_role_index].permissions = response.permissions;
          app.$message('Permission Assigned Successfully');
        });
    },
  },
};
</script>
