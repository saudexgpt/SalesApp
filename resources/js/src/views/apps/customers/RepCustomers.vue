<template>
  <div v-loading="load_table" v-if="page==='list'">
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
          <span class="font-medium text-lg">Reps Customers</span>
        </div>
        <!-- <div class="flex items-end px-3">
          <el-input
            v-model="query.keyword"
            placeholder="Search Customer"
            style="width: 100%"
            class="filter-item"
            @input="handleFilter"
          />
        </div> -->
      </div>
      <vs-divider />
    </div>
    <div>
      <filter-options :submit-on-rep-change="true" :hide-customers-list="true" :show-date-picker="false" @submitQuery="setParam" />
      <!-- <el-button
          :loading="downloading"
          round
          class="filter-item"
          type="primary"
          icon="el-icon-download"
          @click="handleDownload"
        >Export</el-button> -->
      <div v-if="!showStatement">

        <v-client-table
          v-model="list"
          :columns="columns"
          :options="options"
        >

          <template slot="assigned_officer" slot-scope="props">
            <el-select
              v-if="checkPermission(['assign-field-staff'])"
              v-model="props.row.relating_officer"
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
            <span v-else>{{ (props.row.assigned_officer !== null) ? props.row.assigned_officer.name : 'Not Assigned' }}</span>
          </template>
          <template slot="last_visited" slot-scope="scope">
            <span>{{ (scope.row.last_visited) ? moment(scope.row.last_visited.visit_date).format('ll') : 'Not visited' }}</span>
          </template>
          <template slot="created_at" slot-scope="scope">
            <span>{{ moment(scope.row.created_at).format('ll') }}</span>
          </template>
          <template slot="date_verified" slot-scope="scope">
            <span>{{ (scope.row.date_verified) ? moment(scope.row.date_verified).format('ll') : '' }}</span>
          </template>
          <template slot="action" slot-scope="scope">

            <el-button
              icon="el-icon-document"
              type="primary"
              @click="showRepCustomerStatement(scope.row.id)"
            >
              View Statement
            </el-button>
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
      </div>
      <div v-else>
        <rep-customer-statement :customer-id="selectedCustomerId" :rep-id="selectedRepId" @back="showStatement = false;"/>
      </div>
    </div>
  </div>
</template>

<script>
import moment from 'moment';
import VueGoogleAutocomplete from 'vue-google-autocomplete';
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
import FilterOptions from '@/views/apps/reports/FilterOptions';
import RepCustomerStatement from './RepCustomerStatements';
const customersResource = new Resource('customers');
export default {
  name: 'Customers',
  components: { RepCustomerStatement, Pagination, VueGoogleAutocomplete, FilterOptions },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      list: [],
      columns: [
        'action',
        'business_name',
        'code',
        'customer_type.name',
        // 'area',
        'last_visited',
        'registrar.name',
        // 'assigned_officer',
        'address',
        // 'verifier.name',
        'created_at',
        // 'date_verified',
      ],

      options: {
        headings: {
          business_name: 'Name',
          'customer_type.name': 'Type',
          'last_visited': 'Last Visit',
          'registrar.name': 'Registered By',
          // 'assigned_officer': 'Field Staff',
          'verifier.name': 'Verified By',
        },
        rowAttributesCallback(row) {
          if (row.is_duplicate_entry === 1) {
            return { style: 'background: #ffec43f2; color: #000000' };
          }
          // return { style: 'background: #36c15ecf; color: #000000' };
        },
        pagination: {
          dropdown: true,
          chunk: 50,
        },
        perPage: 50,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['business_name', 'code', 'last_visited', 'customer_type.name', 'created_at', 'date_verified'],
        filterable: ['business_name', 'code', 'customer_type.name', 'registrar.name'],
      },
      total: 0,
      loading: false,
      load_table: false,
      downloading: false,
      updatingCustomer: false,
      query: {
        page: 1,
        limit: 50,
        keyword: '',
        role: '',
        verify_type: 'all',
        rep_id: '',
        team_id: '',
      },
      selectedCustomer: {},
      dialogFormVisible: false,
      selected_customer: '',
      page: 'list',
      assignRep: {
        relating_officer: '',
        customer_ids: [],
      },
      selected_state: '',
      states: [],
      lgas: [],
      customer_types: [],
      showStatement: false,
      selectedCustomerId: null,
      selectedRepId: null,
    };
  },
  created() {
    // this.getList();
    // this.fetchSalesRep();
  },
  methods: {
    moment,
    checkPermission,
    showRepCustomerStatement(customerId) {
      const app = this;
      app.selectedCustomerId = customerId;
      app.selectedRepId = app.query.rep_id;
      app.showStatement = true;
    },
    setParam(param) {
      this.query.rep_id = param.rep_id;
      this.query.team_id = param.team_id;
      this.getList();
    },
    getList() {
      const { limit, page } = this.query;
      this.options.perPage = limit;
      this.load_table = true;
      customersResource
        .list(this.query)
        .then((response) => {
          this.states = response.states;
          this.customer_types = response.customer_types;
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
    updateCustomer() {
      const app = this;
      app.updatingCustomer = true;
      const selectedCustomer = app.selectedCustomer;
      const assignOfficerResource = new Resource('customers/update');
      assignOfficerResource
        .update(selectedCustomer.id, selectedCustomer)
        .then(() => {
          app.selectedCustomer = {};
          app.dialogFormVisible = false;
          app.updatingCustomer = false;
          app.$message('Customer Updated Successfully');
          app.getList();
        })
        .catch((error) => {
          app.updatingCustomer = false;
          console.log(error);
        });
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
            app.getList();
            app.load_table = false;
          });
      }).catch(() => {
        app.load_table = false;
      });
    },
    deleteCustomer(index, id, business_name) {
      const app = this;
      const storeResource = new Resource('customers/remove');
      app.$confirm('Are you sure you want to remove ' + business_name + '?', 'Warning', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        app.load_table = true;
        storeResource.destroy(id)
          .then(() => {
            app.$message('Customer Removed Successful');
            // app.customer = response.customer;
            app.list.splice(index - 1, 1);
            app.load_table = false;
          });
      }).catch(() => {
        app.load_table = false;
      });
    },
    unAssignCustomer(index, id, business_name) {
      const app = this;
      const storeResource = new Resource('customers/unassign-customers-that-are-not-mine');
      app.$confirm('Are you sure you want to unassign ' + business_name + '?', 'Warning', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        app.load_table = true;
        storeResource.update(id, { rep_id: app.query.rep_id })
          .then(() => {
            app.$message('Customer Unassigned Successful');
            // app.customer = response.customer;
            app.list.splice(index - 1, 1);
            app.load_table = false;
          });
      }).catch(() => {
        app.load_table = false;
      });
    },
    showCustomerDetails(selectedCustomer){
      const app = this;
      app.selected_customer = selectedCustomer;
      app.page = 'details';
    },
    handleCreate() {
      this.resetNewUser();
      this.dialogFormVisible = true;
      this.$nextTick(() => {
        this.$validator.reset();
      });
    },
    handleFilter() {
      this.query.page = 1;
      this.getList();
    },
    handleDownload(){
      if (confirm('You can only export the current view')) {
        this.export(this.list);
      }
      // fetch all data for export
    //   this.query.limit = this.total;
    //   this.downloading = true;
    //   customersResource.list(this.query)
    //     .then(response => {
    //       this.export(this.customers.data);

    //       this.downloading = false;
    //     });
    },
    export(export_data) {
      import('@/vendor/Export2Excel').then((excel) => {
        const tHeader = [
          'BUSINESS NAME',
          'TYPE',
          'ADDRESS',
          'REGISTERED BY',
          'FIELD STAFF',
          'DATE REGISTERED',
          'LAST VERIFIED DATE',
        ];
        const filterVal = [
          'business_name',
          'customer_type.name',
          'address',
          'registrar.name',
          'assigned_officer.name',
          'created_at',
          'date_verified',
        ];
        const data = this.formatJson(filterVal, export_data);
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'customer-list',
        });
        this.downloading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'customer_type.name') {
            if (v['customer_type'] !== null) {
              return v['customer_type']['name'];
            }
          }
          if (j === 'registrar.name') {
            if (v['registrar'] !== null) {
              return v['registrar']['name'];
            }
          }
          if (j === 'assigned_officer.name') {
            if (v['assigned_officer'] !== null) {
              return v['assigned_officer']['name'];
            }
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
