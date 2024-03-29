<template>
  <div>
    <vx-card v-loading="load_table" v-if="page==='list'">
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
            <span class="font-medium text-lg">List of Unverified Customers</span>
          </div>
          <vs-divider />
        </div>
        <div class="vx-col lg:w-1/4 w-full">
          <div class="flex items-end px-3">
            <span class="pull-right">
              <router-link
                :to="'/customers/map'"
              >
                <el-button
                  round
                  class="filter-item"
                  type="default"
                  icon="el-icon-map-location"
                >Map
                </el-button>
              </router-link>
              <el-button
                :loading="downloading"
                round
                class="filter-item"
                type="primary"
                icon="el-icon-download"
                @click="handleDownload"
              >Export</el-button>
            </span>
          </div>
        </div>
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
          </el-col>
        </el-row>
      </div>
      <el-alert type="error">These customers have NOT been verified for once</el-alert>
      <v-client-table
        v-model="list"
        :columns="columns"
        :options="options"
      >
        <template slot="assigned_officer.name" slot-scope="props">
          <el-select
            v-if="checkPermission(['assign-field-staff'])"
            v-model="props.row.assigned_officer.id"
            placeholder="Select Rep"
            filterable
            @input="assignOfficer($event, props.row.id)"
          >
            <el-option
              v-for="(rep, index) in sales_reps"
              :key="index"
              :label="rep.name"
              :value="rep.id"
            />
          </el-select>
          <span v-else>{{ props.row.assigned_officer.name }}</span>
        </template>
        <template slot="visits" slot-scope="scope">
          <span>{{ (scope.row.visits.length > 0) ? moment(scope.row.visits[0].visit_date).format('ll') : '' }}</span>
        </template>
        <template slot="created_at" slot-scope="scope">
          <span>{{ moment(scope.row.created_at).format('ll') }}</span>
        </template>
        <template slot="date_verified" slot-scope="scope">
          <span>{{ (scope.row.date_verified) ? moment(scope.row.date_verified).format('ll') : '' }}</span>
        </template>
        <template slot="action" slot-scope="scope">
          <el-tooltip
            class="item"
            effect="dark"
            content="View Customer Details"
            placement="top-start"
          >
            <router-link
              :to="'/customer/details/' + scope.row.id"
            >
              <el-button
                round
                type="warning"
                size="small"
                icon="el-icon-view"
              />
            </router-link>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            content="Edit User"
            placement="top-start"
          >
            <router-link
              :to="'/administrator/users/edit/' + scope.row.id"
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
            content="Verify Customer"
            placement="top-start"
          >
            <el-button
              v-permission="['verify-customers']"
              round
              type="success"
              size="small"
              icon="el-icon-check"
              @click="verifyCustomer(scope.index, scope.row.id, scope.row.business_name)"
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

      <!-- <vs-popup
        :active.sync="dialogFormVisible"
        fullscreen
        title="Add New User">
        <div v-loading="userCreating" class="con-exemple-prompt">
          <form >
            <div class="vx-row">
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <vs-input v-model="newCustomer.first_name" v-validate="'required'" name="first_name" label-placeholder="First Name" class="mt-3 w-full" data-vv-validate-on="blur"/>
                <span v-show="errors.has('first_name')" class="text-danger text-sm">{{ errors.first('first_name') }}</span>
              </div>
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <vs-input v-model="newCustomer.last_name" v-validate="'required'" name="last_name" label-placeholder="Last Name" class="mt-3 w-full" data-vv-validate-on="blur"/>
                <span v-show="errors.has('last_name')" class="text-danger text-sm">{{ errors.first('last_name') }}</span>
              </div>
            </div>
            <div class="vx-row">
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <vs-input v-model="newCustomer.email" v-validate="'required'" type="email" name="email" label-placeholder="Email" class="mt-3 w-full" data-vv-validate-on="blur"/>
                <span v-show="errors.has('email')" class="text-danger text-sm">{{ errors.first('email') }}</span>
              </div>
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <vs-input v-model="newCustomer.username" v-validate="'required'" name="username" label-placeholder="Username" class="mt-3 w-full" data-vv-validate-on="blur"/>
                <span v-show="errors.has('username')" class="text-danger text-sm">{{ errors.first('username') }}</span>
              </div>
            </div>
            <div class="vx-row">
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <vs-input v-model="newCustomer.password" v-validate="'required|min:8'" name="password" type="password" show-password label-placeholder="Password" class="mt-3 w-full" data-vv-validate-on="blur"/>
                <span v-show="errors.has('password')" class="text-danger text-sm">{{ errors.first('password') }}</span>
              </div>
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <vs-input v-model="newCustomer.confirmPassword" v-validate="'required|min:8|confirmed:password'" name="confirm-password" type="password" show-password label-placeholder="Confirm Password" class="mt-3 w-full" data-vv-validate-on="blur"/>
                <span v-show="errors.has('confirm-password')" class="text-danger text-sm">{{ errors.first('confirm-password') }}</span>
              </div>
            </div>

            <div class="dialog-footer">
              <vs-button color="danger" type="filled" @click="dialogFormVisible = false">Cancel</vs-button>
              <vs-button color="success" type="filled" @click.prevent="createUser()">Submit</vs-button>
            </div>
          </form>
        </div>
      </vs-popup> -->
    </vx-card>
  </div>
</template>

<script>
import moment from 'moment';
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
const customersResource = new Resource('customers/prospective');
export default {
  name: 'Customers',
  components: { Pagination },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      list: [],
      columns: [
        'business_name',
        'customer_type.name',
        'address',
        'area',
        'visits',
        'registrar.name',
        'assigned_officer.name',
        'created_at',
        'action',
      ],

      options: {
        headings: {
          business_name: 'Name',
          'customer_type.name': 'Type',
          'visits': 'Last Visit',
          'registrar.name': 'Registered By',
          'assigned_officer.name': 'Field Staff',
          'verifier.name': 'Verified By',
        },
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
        sortable: ['created_at', 'date_verified'],
        filterable: ['business_name', 'customer_type.name', 'area', 'registrar.name', 'assigned_officer.name'],
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
      newCustomer: {},
      dialogFormVisible: false,
      selected_customer: '',
      page: 'list',
    };
  },
  created() {
    this.fetchSalesRep();
    this.getList();
  },
  methods: {
    moment,
    checkPermission,
    fetchSalesRep() {
      const app = this;
      const salesRepResource = new Resource('users/fetch-sales-reps');
      salesRepResource
        .list()
        .then((response) => {
          app.sales_reps = response.sales_reps;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    getList() {
      const { limit, page } = this.query;
      this.options.perPage = limit;
      this.load_table = true;
      customersResource
        .list(this.query)
        .then((response) => {
          const customers = response.customers;
          this.list = customers.data;
          this.list.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = customers.total;
          this.load_table = false;
        })
        .catch((error) => {
          console.log(error);
          this.load_table = false;
        });
    },
    assignOfficer(staffId, customer_id) {
      const app = this;
      const customer_ids = [customer_id];
      const assignOfficerResource = new Resource('customers/assign-field-staff');
      assignOfficerResource
        .update(staffId, { customer_ids: customer_ids })
        .then(() => {
          app.assignRep = {
            relating_officer: '',
            customer_ids: [],
          };
          app.$message('Officer Assigned Successfully');
          app.getList();
        })
        .catch((error) => {
          console.log(error);
        });
    },
    showCustomerDetails(selectedCustomer){
      const app = this;
      app.selected_customer = selectedCustomer;
      app.page = 'details';
    },
    verifyCustomer(index, id, business_name) {
      const app = this;
      const storeResource = new Resource('customers/verify');
      app.$confirm('Are you sure you want to verify ' + business_name + '?', 'Warning', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        app.load_table = true;
        storeResource.update(id)
          .then(() => {
            app.$message('Action Successful');
            // app.customer = response.customer;
            app.list.splice(index - 1, 1);
            app.load_table = false;
          });
      }).catch(() => {
        app.load_table = false;
      });
    },
    // confirmCustomer(index, id, business_name) {
    //   const app = this;
    //   const confirmResource = new Resource('customers/confirm');
    //   app.$confirm('Are you sure you want to confirm ' + business_name + '?', 'Warning', {
    //     confirmButtonText: 'Yes',
    //     cancelButtonText: 'Cancel',
    //     type: 'warning',
    //   }).then(() => {
    //     app.load_table = true;
    //     confirmResource.update(id)
    //       .then(() => {
    //         app.list.splice(index - 1, 1);
    //         app.load_table = false;
    //       });
    //   }).catch(() => {
    //     app.load_table = false;
    //   });
    // },
    handleFilter() {
      this.query.page = 1;
      this.getList();
    },
    handleDownload(){
      // fetch all data for export
      this.query.limit = this.total;
      this.downloading = true;
      customersResource.list(this.query)
        .then(response => {
          this.export(response.data);

          this.downloading = false;
        });
    },
    export(export_data) {
      import('@/vendor/Export2Excel').then((excel) => {
        const tHeader = [
          'name',
          'email',
          'phone',
          'address',
        ];
        const filterVal = [
          'name',
          'email',
          'phone',
          'address',
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
