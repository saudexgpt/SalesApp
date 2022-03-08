<template>
  <div>
    <vx-card v-loading="load_table" v-if="page==='list'">
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
            <span class="font-medium text-lg">List of Sales Reps</span>
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
              <el-select
                v-model="assignRep.customer_ids"
                placeholder="Select Customers (Multiple Allowed)"
                multiple
                filterable
                collapse-tags
              >
                <el-option
                  v-for="(customer, customer_index) in customer_list"
                  :key="customer_index"
                  :label="customer.business_name"
                  :value="customer.id"
                />
              </el-select>
              <el-button
                :disabled="customer_list.length < 1"
                type="primary"
                @click="assignOfficer()"
              >
                Assign
              </el-button>
            </aside>
          </el-col>
        </el-row>
      </div>
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
                type="success"
                size="small"
                icon="el-icon-view"
              />
            </router-link>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            content="View Customer Statement"
            placement="top-start"
          >
            <router-link
              :to="'/report/customer-statement/' + scope.row.id"
            >
              <el-button
                round
                type="warning"
                size="small"
                icon="el-icon-document"
              />
            </router-link>
          </el-tooltip>
        </template>
      </v-client-table>
    </vx-card>
  </div>
</template>

<script>
import moment from 'moment';
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking

export default {
  name: 'Customers',
  components: { Pagination },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
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
      downloading: false,
      userCreating: false,
      dialogFormVisible: false,
      selected_customer: '',
      page: 'list',
      assignRep: {
        relating_officer: '',
        customer_ids: [],
      },
      selected_lga: '',
      customer_list: [],
    };
  },
  created() {
    this.fetchLGACustomers();
    this.fetchSalesRep();
  },
  methods: {
    moment,
    checkPermission,
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
    fetchLGACustomers() {
      const app = this;
      const customersResource = new Resource('customers/lga-customers');
      customersResource
        .list()
        .then((response) => {
          app.lgas = response.lgas;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    assignOfficer() {
      const app = this;
      const staffId = app.assignRep.relating_officer;
      const customer_ids = app.assignRep.customer_ids;
      const assignOfficerResource = new Resource('customers/assign-field-staff');
      assignOfficerResource
        .update(staffId, { customer_ids: customer_ids })
        .then(() => {
          app.assignRep = {
            relating_officer: '',
            customer_ids: [],
          };
          app.$message('Officer Assigned Successfully');
          app.fetchSalesRep();
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
