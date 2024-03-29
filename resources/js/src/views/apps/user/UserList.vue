<template>
  <div class="app-container">

    <el-card v-loading="load_table" class="box-card">
      <div slot="header" class="clearfix">
        <span>List of Users</span>
      </div>
      <div class="filter-container">
        <el-row :gutter="20">
          <el-col :xs="24" :sm="12" :md="12">
            <el-input
              v-model="query.keyword"
              placeholder="Search User"
              style="width: 200px"
              class="filter-item"
              @input="handleFilter"
            />
            <el-select
              v-model="query.role"
              placeholder="Filter By Role"
              clearable
              style="width: 50%"
              class="filter-item"
              @change="handleFilter"
            >
              <el-option
                v-for="role in roles"
                :key="role.name"
                :label="role.display_name"
                :value="role.name"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="12" :md="12">
            <span class="pull-right">
              <el-button
                v-permission="['create-users']"
                round
                class="filter-item"
                type="success"
                icon="el-icon-plus"
                @click="handleCreate"
              >Add User
              </el-button>
              <el-button
                :loading="downloading"
                round
                class="filter-item"
                type="primary"
                icon="el-icon-download"
                @click="handleDownload"
              >Export</el-button>
            </span>
          </el-col>
        </el-row>
      </div>
      <v-client-table
        v-if="list.length > 0"
        v-model="list"
        :columns="columns"
        :options="options"
      >
        <template slot="role" slot-scope="scope">
          <span :id="scope.row.id">{{ scope.row.roles.join(', ') }}</span>
        </template>
        <template slot="last_login" slot-scope="{row}">
          <span>{{ (row.last_login) ? moment(row.last_login).format('lll') : 'Never' }}</span>
        </template>
        <template slot="product_dealing" slot-scope="{ row }">
          <el-select
            v-if="row.roles.includes('sales_rep')"
            v-model="row.product_type"
            class="filter-item"
            placeholder="Please select product category"
            @change="setUserProductDealingType(row, $event)"
          >
            <el-option
              label="Fast Moving Products"
              value="fast_moving_products"
            />
            <el-option
              label="Pharmaceutical Products"
              value="pharmaceutical_products"
            />
          </el-select>
        </template>
        <template slot="assign_role" slot-scope="{ row }">
          <el-select
            v-if="!row.roles.includes('super')"
            v-model="row.new_role"
            class="filter-item"
            placeholder="Please select role"
            @change="assignUserRole(row, $event)"
          >
            <el-option
              v-for="role in roles"
              :key="role.name"
              :label="role.display_name"
              :value="role.name"
            />
          </el-select>
        </template>
        <template slot="action" slot-scope="scope">
          <el-tooltip
            class="item"
            effect="dark"
            content="Edit User"
            placement="top-start"
          >
            <router-link
              :to="'/settings/users/edit/' + scope.row.id"
            >
              <el-button
                v-permission="['update-users']"
                round
                type="primary"
                size="small"
                icon="el-icon-edit"
              />
            </router-link>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            content="Reset Password"
            placement="top-start"
          >
            <el-button
              v-permission="['update-users']"
              round
              type="warning"
              size="small"
              icon="el-icon-key"
              @click="resetUserPassword(scope.row.id, scope.row.name)"
            />
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            content="Delete User"
            placement="top-start"
          >
            <el-button
              v-permission="['delete-users']"
              v-if="!scope.row.roles.includes('admin')"
              round
              type="danger"
              size="small"
              icon="el-icon-delete"
              @click="handleDelete(scope.index, scope.row.id, scope.row.name)"
            />
          </el-tooltip>
        </template>
      </v-client-table>
      <el-row :gutter="20">
        <pagination
          v-show="total > 0"
          :total="total"
          :page.sync="query.page"
          :limit.sync="query.limit"
          @pagination="getList"
        />
      </el-row>
    </el-card>
    <vs-popup
      :active.sync="dialogFormVisible"
      fullscreen
      title="Add New User">
      <div v-loading="userCreating" class="con-exemple-prompt">
        <form >
          <div class="vx-row">
            <div class="vx-col sm:w-1/2 w-full mb-2">
              <vs-input v-validate="'required'" v-model="newUser.first_name" name="first_name" label-placeholder="First Name" class="mt-3 w-full" data-vv-validate-on="blur"/>
              <span v-show="errors.has('first_name')" class="text-danger text-sm">{{ errors.first('first_name') }}</span>
            </div>
            <div class="vx-col sm:w-1/2 w-full mb-2">
              <vs-input v-validate="'required'" v-model="newUser.last_name" name="last_name" label-placeholder="Last Name" class="mt-3 w-full" data-vv-validate-on="blur"/>
              <span v-show="errors.has('last_name')" class="text-danger text-sm">{{ errors.first('last_name') }}</span>
            </div>
          </div>
          <div class="vx-row">
            <div class="vx-col sm:w-1/2 w-full mb-2">
              <vs-input v-validate="'required'" v-model="newUser.email" type="email" name="email" label-placeholder="Email" class="mt-3 w-full" data-vv-validate-on="blur"/>
              <span v-show="errors.has('email')" class="text-danger text-sm">{{ errors.first('email') }}</span>
            </div>
            <div class="vx-col sm:w-1/2 w-full mb-2">
              <vs-input v-validate="'required'" v-model="newUser.username" name="username" label-placeholder="Username" class="mt-3 w-full" data-vv-validate-on="blur"/>
              <span v-show="errors.has('username')" class="text-danger text-sm">{{ errors.first('username') }}</span>
            </div>
          </div>
          <div class="vx-row">
            <div class="vx-col sm:w-1/2 w-full mb-2">
              <vs-input v-validate="'required'" v-model="newUser.phone" name="phone" label-placeholder="Phone" class="mt-3 w-full" data-vv-validate-on="blur"/>
              <span v-show="errors.has('phone')" class="text-danger text-sm">{{ errors.first('phone') }}</span>
            </div>
            <div class="vx-col sm:w-1/2 w-full mb-2">
              <!-- <vs-select
                v-model="newUser.role"
                placeholder="Select Role"
                label="Select Role"
                label-placeholder="Select Role"
                style="width: 100%"
              >
                <vs-select-item
                  v-for="(role,index) in roles"
                  :key="index"
                  :value="role.name"
                  :text="role.display_name"
                  :disabled="role.name === 'sales_rep'"
                />
              </vs-select> -->
              <label>&nbsp;</label>
              <el-select
                v-model="newUser.role"
                placeholder="Select Role"
                style="width: 100%"
              >
                <el-option
                  v-for="(role,index) in roles"
                  :key="index"
                  :value="role.name"
                  :label="role.display_name"
                  :disabled="role.name === 'sales_rep'"
                />
              </el-select>
            </div>
          </div>
          <div class="vx-row"/>
          <!-- <div class="vx-row">
            <div class="vx-col sm:w-1/2 w-full mb-2">
              <vs-input v-validate="'required|min:8'" v-model="newUser.password" name="password" type="password" show-password label-placeholder="Password" class="mt-3 w-full" data-vv-validate-on="blur"/>
              <span v-show="errors.has('password')" class="text-danger text-sm">{{ errors.first('password') }}</span>
            </div>
            <div class="vx-col sm:w-1/2 w-full mb-2">
              <vs-input v-validate="'required|min:8|confirmed:password'" v-model="newUser.confirmPassword" :show-password="true" name="confirm_password" type="password" label-placeholder="Confirm Password" class="mt-3 w-full" data-vv-validate-on="blur"/>
              <span v-show="errors.has('confirm_password')" class="text-danger text-sm">{{ errors.first('confirm_password') }}</span>
            </div>
          </div> -->

          <div class="dialog-footer">
            <vs-button color="danger" type="filled" @click="dialogFormVisible = false">Cancel</vs-button>
            <vs-button color="success" type="filled" @click.prevent="createUser()">Submit</vs-button>
          </div>
        </form>
      </div>
    </vs-popup>
  </div>
</template>

<script>
import moment from 'moment';
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import UserResource from '@/api/user';
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking

const userResource = new UserResource();
const resetUserPasswordResource = new Resource('users/reset-password');
const deleteUserResource = new Resource('users');
const assignRoleResource = new Resource('users/assign-role');
const necessaryParams = new Resource('fetch-necessary-params');
export default {
  name: 'ManageUsers',
  components: { Pagination },
  directives: { permission },
  props: {
    canAddNew: {
      type: Boolean,
      default: () => true,
    },
  },
  data() {
    return {
      list: [],
      columns: [
        'name',
        'email',
        'phone',
        'role',
        'last_login',
        'assign_role',
        'product_dealing',
        'action',
      ],

      options: {
        headings: {},
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['name', 'email', 'phone'],
        filterable: ['name', 'email', 'phone'],
      },
      total: 0,
      loading: false,
      load_table: false,
      downloading: false,
      userCreating: false,
      query: {
        page: 1,
        limit: 10,
        keyword: '',
        role: '',
      },
      roles: [],
      defaultRoles: [],
      states: [],
      lgas: [],
      newUser: {
        first_name: '',
        last_name: '',
        name: '',
        email: '',
        phone: '',
        password: 'password',
        confirmPassword: 'password',
        role: 'admin',
      },
      manager_details: {
        type: '',
        domain_ids: [],
      },
      dialogFormVisible: false,
      dialogPermissionVisible: false,
      dialogPermissionLoading: false,
      currentUserId: 0,
      currentUser: {
        name: '',
        permissions: [],
        rolePermissions: [],
      },
      permissionProps: {
        children: 'children',
        label: 'name',
        disabled: 'disabled',
      },
      permissions: [],
      menuPermissions: [],
      otherPermissions: [],
      new_role: '',
    };
  },
  created() {
    this.getList();
    this.fetchNecessaryParams();
    this.resetNewUser();
  },
  methods: {
    moment,
    checkPermission,
    fetchNecessaryParams() {
      const app = this;
      necessaryParams.list().then((response) => {
        // app.roles = response.params.all_roles;
        app.roles = response.params.default_roles;
        app.defaultRoles = response.params.default_roles;
        app.states = response.params.states;
        app.lgas = response.params.lgas;
        // if (app.warehouses.length > 0) {
        //   app.form.warehouse_id = app.warehouses[0];
        //   app.form.warehouse_index = 0;
        //   app.getWaybills();
        // }
      });
    },
    getList() {
      const { limit, page } = this.query;
      this.options.perPage = limit;
      this.load_table = true;
      userResource
        .list(this.query)
        .then((response) => {
          this.list = response.data;
          this.list.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.meta.total;
          this.load_table = false;
        })
        .catch((error) => {
          console.log(error);
          this.load_table = false;
        });
    },
    handleFilter() {
      this.query.page = 1;
      this.getList();
    },
    handleCreate() {
      this.resetNewUser();
      this.dialogFormVisible = true;
      this.$nextTick(() => {
        this.$validator.reset();
      });
    },
    resetUserPassword(id, name) {
      this.$confirm(
        'This will reset the password of ' + name + '. Continue?',
        'Warning',
        {
          confirmButtonText: 'OK',
          cancelButtonText: 'Cancel',
          type: 'warning',
        },
      )
        .then(() => {
          this.loading = true;
          resetUserPasswordResource
            .update(id)
            .then((response) => {
              // this.$store.dispatch('user/resetPasswordStatus', {
              //   p_status: 'default',
              // });
              this.$message({
                type: 'success',
                message: 'Password Changed',
              });
              alert(
                'New Password for ' + name + ' is: ' + response.new_password,
              );
              this.handleFilter();
              this.loading = false;
            })
            .catch((error) => {
              console.log(error);
              this.loading = false;
            });
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: 'Password Reset canceled',
          });
        });
    },
    handleDelete(index, id, name) {
      this.$confirm(
        'This will delete the account of ' + name + '. Continue?',
        'Warning',
        {
          confirmButtonText: 'OK',
          cancelButtonText: 'Cancel',
          type: 'warning',
        },
      )
        .then(() => {
          this.loading = true;
          deleteUserResource
            .destroy(id)
            .then((response) => {
              this.$message({
                type: 'success',
                message: 'Delete completed',
              });
              this.list.splice(index - 1, 1);
              this.loading = false;
            })
            .catch((error) => {
              console.log(error);
              this.loading = false;
            });
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: 'Delete Action Canceled',
          });
        });
    },
    createUser() {
      this.$validator.validateAll().then((valid) => {
        if (valid) {
          this.userCreating = true;
          const param = this.newUser;
          param.manager_details = this.manager_details;
          userResource
            .store(param)
            .then((response) => {
              this.userCreating = false;
              this.$message({
                message:
                  'New user ' +
                  this.newUser.name +
                  '(' +
                  this.newUser.email +
                  ') has been created successfully.',
                type: 'success',
                duration: 5 * 1000,
              });
              this.resetNewUser();
              this.dialogFormVisible = false;
              this.handleFilter();
              this.$alert('The default password is: password and should be changed on first login');
            })
            .catch((error) => {
              this.userCreating = false;
              let issues = '';
              const errors = error.response.data.errors;
              for (const key in errors) {
                if (Object.hasOwnProperty.call(errors, key)) {
                  issues += errors[key][0] + '<br>';
                }
              }
              console.log(issues);
              this.$alert(issues, 'Issues Found', {
                dangerouslyUseHTMLString: true,
              });
              // alert(issues);
            });
        } else {
          console.log('error submit!!');
          return false;
        }
      });
    },

    setUserProductDealingType(user, type) {
      const app = this;
      const setUserProductTypeResource = new Resource('users/set-product-type');
      setUserProductTypeResource
        .update(user.id, { product_type: type })
        .then(() => {
          app.$message({
            type: 'success',
            message: 'Action Successful',
          });
        });
    },
    assignUserRole(user, role) {
      this.$confirm(
        user.name + ' will be assigned the role of ' + role + '. Continue?',
        'Warning',
        {
          confirmButtonText: 'OK',
          cancelButtonText: 'Cancel',
          type: 'warning',
        },
      )
        .then(() => {
          this.loading = true;
          assignRoleResource
            .update(user.id, { role: role })
            .then((response) => {
              this.$message({
                type: 'success',
                message: 'Role assigned',
              });
              document.getElementById(
                user.id,
              ).innerHTML = response.data.roles.join(', ');
              this.loading = false;
            })
            .catch((error) => {
              console.log(error);
              this.loading = false;
            });
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: 'Action Canceled',
          });
        });
    },
    resetNewUser() {
      this.newUser = {
        name: '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        password: '',
        confirmPassword: '',
        role: '',
      };
      this.manager_details = {
        type: '',
        domain_ids: [],
      };
    },
    handleDownload(){
      // fetch all data for export
      this.query.limit = this.total;
      this.downloading = true;
      userResource.list(this.query)
        .then(response => {
          this.export(response.data);

          this.downloading = false;
        });
    },
    export(export_data) {
      import('@/vendor/Export2Excel').then((excel) => {
        const tHeader = [
          'NAME',
          'EMAIL',
          'PHONE',
          'ADDRESS',
          'ROLE',
          'LAST LOGIN',
        ];
        const filterVal = [
          'name',
          'email',
          'phone',
          'address',
          'role',
          'last_login',
        ];
        const data = this.formatJson(filterVal, export_data);
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'user-list',
        });
        this.downloading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'role') {
            return v['roles'].join(', ');
          }
          return v[j];
        }),
      );
    },
    permissionKeys(permissions) {
      return permissions.map((permssion) => permssion.id);
    },
    classifyPermissions(permissions) {
      const all = [];
      const menu = [];
      const other = [];
      permissions.forEach((permission) => {
        const permissionName = permission.name;
        all.push(permission);
        if (permissionName.startsWith('view menu')) {
          menu.push(this.normalizeMenuPermission(permission));
        } else {
          other.push(this.normalizePermission(permission));
        }
      });
      return { all, menu, other };
    },

    confirmPermission() {
      const checkedMenu = this.$refs.menuPermissions.getCheckedKeys();
      const checkedOther = this.$refs.otherPermissions.getCheckedKeys();
      const checkedPermissions = checkedMenu.concat(checkedOther);
      this.dialogPermissionLoading = true;

      userResource
        .updatePermission(this.currentUserId, {
          permissions: checkedPermissions,
        })
        .then((response) => {
          this.$message({
            message: 'Permissions has been updated successfully',
            type: 'success',
            duration: 5 * 1000,
          });
          this.dialogPermissionLoading = false;
          this.dialogPermissionVisible = false;
        });
    },
  },
};
</script>
<style>
.vs-con-input {
    margin-top: 20px !important ;
}
.dialog-footer {
    background: #f0f0f0;
    padding: 10px;
    margin-top: 20px !important ;
    position: relative;
}
</style>
