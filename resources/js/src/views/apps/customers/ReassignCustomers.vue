<template>
  <div>
    <vx-card v-loading="load_table">
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <!-- <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
            <span class="font-medium text-lg">Reassign Customers to Reps</span>
          </div> -->
          <div class="flex items-end px-3">
            <el-input
              v-model="query.keyword"
              placeholder="Search Customer"
              style="width: 100%"
              class="filter-item"
              @input="handleFilter"
            />
          </div>
          <vs-divider />
        </div>
      </div>
      <div class="filter-container">
        <!-- <el-row v-if="checkPermission(['assign-field-staff'])" :gutter="20">
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
                v-model="assignRep.customer_ids"
                placeholder="Select Customers"
                multiple
                filterable
                collapse-tags
              >
                <el-option
                  v-for="(customer, customer_index) in list"
                  :key="customer_index"
                  :label="customer.business_name"
                  :value="customer.id"
                />
              </el-select>
              <el-button type="primary" @click="assignOfficer()">Assign</el-button>
            </aside>
          </el-col>
        </el-row> -->
        <el-row :gutter="20">
          <!-- <el-col :xs="24" :sm="8" :md="8">
            <p>Select Reps Customers</p>
            <el-select
              v-model="query.rep_id"
              placeholder="Select Rep"
              filterable
              style="width: 100%"
              @input="getList()"
            >
              <el-option
                v-for="(rep, index) in sales_reps"
                :key="index"
                :label="rep.name"
                :value="rep.id"
              />
            </el-select>
          </el-col> -->
          <el-col :xs="24" :sm="8" :md="8">
            <p>Select Rep to Assign to</p>
            <el-select
              v-model="form.new_rep"
              placeholder="Select Rep"
              filterable
              style="width: 100%"
            >
              <el-option
                v-for="(rep, index) in sales_reps"
                :key="index"
                :label="rep.name"
                :value="rep.id"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="8" :md="8">
            <p>&nbsp;</p>
            <el-button
              type="primary"
              @click="assignOfficer()"
            >
              Assign
            </el-button>
          </el-col>
        </el-row>
      </div>
      <div>
        <!-- <el-button
          :loading="downloading"
          round
          class="filter-item"
          type="primary"
          icon="el-icon-download"
          @click="handleDownload"
        >Export</el-button> -->
        <v-client-table
          v-model="list"
          :columns="columns"
          :options="options"
        >

          <template slot="business_name" slot-scope="props">
            <span>{{ props.row.business_name }}</span><br>
            <small>{{ props.row.address }}</small><br>
            <small>{{ props.row.latitude }},{{ props.row.longitude }}</small>
          </template>
          <!-- <template slot="assigned_officer" slot-scope="props">
            <span>{{ (props.row.assigned_officer !== null) ? props.row.assigned_officer.name : 'Not Assigned' }}</span>
          </template> -->
          <template slot="last_visited" slot-scope="scope">
            <span>{{ (scope.row.last_visited) ? moment(scope.row.last_visited.visit_date).format('ll') : 'Not visited' }}</span>
          </template>
          <template slot="created_at" slot-scope="scope">
            <span>{{ moment(scope.row.created_at).format('ll') }}</span>
          </template>
          <template slot="date_verified" slot-scope="scope">
            <span>{{ (scope.row.date_verified) ? moment(scope.row.date_verified).format('ll') : '' }}</span>
          </template>
          <template slot="id" slot-scope="scope">
            <el-checkbox v-model="form.customer_ids" :label="scope.row.id" border />
          </template>
        </v-client-table>
      </div>
      <el-row :gutter="20">
        <pagination
          v-show="total > 0"
          :total="total"
          :page.sync="query.page"
          :limit.sync="query.limit"
          @pagination="getList"
        />
      </el-row>
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
      list: [],
      columns: [
        'id',
        'business_name',
        'code',
        'customer_type.name',
        // 'area',
        'last_visited',
        'registrar.name',
        // 'assigned_officer',
        // 'verifier.name',
        'created_at',
        // 'date_verified',
      ],

      options: {
        headings: {
          id: 'ID',
          business_name: 'Name',
          'customer_type.name': 'Type',
          'last_visited': 'Last Visit',
          'registrar.name': 'Registered By',
          // 'assigned_officer': 'Field Staff',
          'verifier.name': 'Verified By',
        },
        // rowAttributesCallback(row) {
        //   if (row.is_duplicate_entry === 1) {
        //     return { style: 'background: #ffec43f2; color: #000000' };
        //   }
        //   // return { style: 'background: #36c15ecf; color: #000000' };
        // },
        pagination: {
          dropdown: true,
          chunk: 500,
        },
        perPage: 500,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['id', 'business_name', 'code'],
        filterable: ['id', 'business_name', 'code'],
      },
      total: 0,
      loading: false,
      load_table: false,
      downloading: false,
      form: {
        customer_ids: [],
        new_rep: '',
      },
      query: {
        page: 1,
        rep_id: '',
        verify_type: 'all',
        keyword: '',
        limit: 50,
        role: '',
        team_id: '',
      },
    };
  },
  created() {
    this.fetchSalesRep();
    this.getList();
  },
  methods: {
    moment,
    checkPermission,
    handleFilter() {
      this.query.page = 1;
      this.getList();
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
    getList() {
      const { limit, page } = this.query;
      this.options.perPage = limit;
      this.load_table = true;
      const customersResource = new Resource('customers');
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
    // getList() {
    //   this.load_table = true;

    //   const customersResource = new Resource('customers');
    //   customersResource
    //     .list(this.query)
    //     .then((response) => {
    //       this.list = response.customers;
    //       this.load_table = false;
    //     })
    //     .catch((error) => {
    //       console.log(error);
    //       this.load_table = false;
    //     });
    // },
    assignOfficer() {
      const app = this;
      app.load_table = true;
      const param = app.form;
      const assignOfficerResource = new Resource('customers/assign-field-staff');
      assignOfficerResource
        .update(param.new_rep, { customer_ids: param.customer_ids })
        .then(() => {
          app.form = {
            new_rep: '',
            customer_ids: [],
          };
          app.$message('Officer Assigned Successfully');
          app.getList();
          app.load_table = false;
        })
        .catch((error) => {
          console.log(error);
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
