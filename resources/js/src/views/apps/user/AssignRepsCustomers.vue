<template>
  <div>
    <vx-card v-loading="load_table" v-if="page==='list'">
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
            <span class="font-medium text-lg">Assign Sales Rep Customers</span>
          </div>
          <vs-divider />
        </div>
      </div>
      <div class="filter-container">
        <el-row v-if="checkPermission(['assign-field-staff'])" :gutter="20">
          <el-col :xs="24" :sm="24" :md="24">
            <h4>Assign Rep to Customers</h4>
            <aside>
              <el-select
                v-model="assignRep.relating_officer"
                placeholder="Select Rep"
                filterable
              >
                <el-option
                  v-for="(rep, index) in sales_reps"
                  :key="index"
                  :label="rep.name"
                  :value="rep.id"
                />
              </el-select>
              <el-select
                v-model="selected_state"
                placeholder="Select State"
                filterable
                @input="setStateLGAs()"
              >
                <el-option
                  v-for="(state, state_index) in states"
                  :key="state_index"
                  :label="state.name"
                  :value="state_index"
                />
              </el-select>
              <el-select
                v-model="selected_lga"
                placeholder="Select LGA"
                filterable
                @input="setLGACustomers()"
              >
                <el-option
                  v-for="(lga, lga_index) in lgas"
                  :key="lga_index"
                  :label="lga.name"
                  :value="lga_index"
                />
              </el-select>

              <!-- <el-select
                v-model="assignRep.customer_ids"
                placeholder="Select Customers (Multiple Allowed)"
                multiple
                filterable
                collapse-tags
                @input="showCustomer()"
              >
                <el-option
                  v-for="(customer, customer_index) in customer_list"
                  :key="customer_index"
                  :label="customer.business_name + ' at ' + customer.area"
                  :value="customer.id"
                >
                  <span style="float: left">{{ '('+ customer.id + ') ' + customer.business_name }}</span>
                  <span style="float: right; color: #8492a6; font-size: 13px">{{ ' at ' + customer.area }}</span>
                </el-option>
              </el-select> -->
            </aside>
          </el-col>
          <el-tag
            v-for="tag in assignRep.customer_ids"
            :key="tag"
            :disable-transitions="false"
            type="danger"
            closable
            @close="handleClose(tag)"
          >
            {{ tag }}
          </el-tag>
        </el-row>
      </div>
      <el-row>
        <el-col :xs="24" :sm="24" :md="24">
          <h3>Selected LGA Customers
            <el-checkbox v-if="customer_list.length > 0" :indeterminate="isIndeterminate" v-model="checkAll" @change="handleCheckAllChange">Check all</el-checkbox>

            <el-button
              :disabled="customer_list.length < 1"
              type="primary"
              @click="assignOfficer()"
            >
              Assign
            </el-button>
          </h3>
          <v-client-table
            v-model="customer_list"
            :columns="['select','business_name', 'address', 'area']"
            :options="{}"
          >

            <template slot="select" slot-scope="{row}">
              <el-checkbox v-model="assignRep.customer_ids" :label="row.id" />
            </template>
            <template slot="action" slot-scope="{row}">
              <el-tooltip
                class="item"
                effect="dark"
                content="View Rep Details"
                placement="top-start"
              >
                <el-button
                  round
                  type="success"
                  size="small"
                  icon="el-icon-view"
                  @click="showRepDetails(row)"
                />
              </el-tooltip>
            </template>
          </v-client-table>
        </el-col>
        <el-col :xs="24" :sm="24" :md="24">
          <h3>LIST OF SALES REPS</h3>
          <v-client-table
            v-model="sales_reps"
            :columns="columns"
            :options="options"
          >

            <template slot="photo" slot-scope="props">
              <img :src="props.row.photo" width="100">
            </template>
            <template slot="customers" slot-scope="props">
              <span>{{ props.row.customers.length }}</span>
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
            <template slot="action" slot-scope="{row}">
              <el-tooltip
                class="item"
                effect="dark"
                content="View Rep Details"
                placement="top-start"
              >
                <el-button
                  round
                  type="success"
                  size="small"
                  icon="el-icon-view"
                  @click="showRepDetails(row)"
                />
              </el-tooltip>
            </template>
          </v-client-table>
        </el-col>
      </el-row>
    </vx-card>
    <vx-card v-if="page==='details'">
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
            <span class="font-medium text-lg">Rep Details</span>
          </div>
          <vs-divider />
        </div>
        <div class="vx-col lg:w-1/4 w-full">
          <div class="flex items-end px-3">
            <el-button
              round
              class="filter-item"
              type="danger"
              @click="page='list'"
            >Back</el-button>
          </div>
        </div>
      </div>
      <div class="filter-container">
        <rep-details :selected-rep="selectedRep"/>
      </div>
    </vx-card>
  </div>
</template>

<script>
import moment from 'moment';
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
import RepDetails from './RepDetails';
export default {
  name: 'Customers',
  components: { Pagination, RepDetails },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      states: [],
      lgas: [],
      columns: [
        // 'photo',
        'name',
        'email',
        'phone',
        'customers',
        'action',
      ],

      options: {
        headings: {
          customers: 'No of Customers',
        },
        pagination: {
          dropdown: true,
          chunk: 100,
        },
        perPage: 100,
        filterByColumn: true,
        texts: {
          filter: 'Search:',
        },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['name', 'email'],
        filterable: ['name', 'email', 'phone'],
      },
      total: 0,
      loading: false,
      load_table: false,
      page: 'list',
      assignRep: {
        relating_officer: '',
        customer_ids: [],
      },
      selected_state: '',
      selected_lga: '',
      customer_list: [],
      selectedRep: null,
      isIndeterminate: false,
      checkAll: null,
    };
  },
  created() {
    this.fetchStateLGACustomers();
    this.fetchSalesRep();
  },
  methods: {
    moment,
    checkPermission,
    handleCheckAllChange(val) {
      const all_customers = [];
      this.customer_list.forEach(element => {
        all_customers.push(element.id);
      });
      this.assignRep.customer_ids = val ? all_customers : [];
      this.isIndeterminate = false;
    },
    showRepDetails(rep) {
      this.selectedRep = rep;
      this.page = 'details';
    },
    handleClose(tag) {
      this.assignRep.customer_ids.splice(this.assignRep.customer_ids.indexOf(tag), 1);
    },
    setStateLGAs() {
      const app = this;
      app.lga = [];
      app.lgas = app.states[app.selected_state].lgas;
    },
    setLGACustomers() {
      const app = this;
      app.customer_list = [];
      app.customer_list = app.lgas[app.selected_lga].customers;
    },
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
    fetchStateLGACustomers() {
      const app = this;
      const customersResource = new Resource('customers/state-lga-customers');
      customersResource
        .list()
        .then((response) => {
          app.states = response.states;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    assignOfficer() {
      const app = this;
      app.load_table = true;
      const staffId = app.assignRep.relating_officer;
      const customer_ids = app.assignRep.customer_ids;
      const assignOfficerResource = new Resource('customers/assign-field-staff');
      assignOfficerResource
        .update(staffId, { customer_ids: customer_ids })
        .then(() => {
          app.load_table = false;
          app.assignRep = {
            relating_officer: '',
            customer_ids: [],
          };
          app.$message('Officer Assigned Successfully');
          app.fetchSalesRep();
        })
        .catch((error) => {
          app.load_table = false;
          console.log(error);
        });
    },
    showCustomerDetails(selectedCustomer){
      const app = this;
      app.selected_customer = selectedCustomer;
      app.page = 'details';
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
