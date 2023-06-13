<template>
  <div v-loading="load_table" v-if="page==='list'">
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
          <span class="font-medium text-lg">List of Customers</span>
        </div>
        <div class="flex items-end px-3">
          <el-input
            v-model="query.keyword"
            placeholder="Search Customer"
            style="width: 100%"
            class="filter-item"
            @input="handleFilter"
          />
        </div>
      </div>
      <!-- <div class="vx-col lg:w-1/4 w-full">
        <div class="flex items-end px-3">
          <span class="pull-right">
            <router-link
              :to="'/customers/map'"
            >
              <el-button
                round
                class="filter-item"
                type="danger"
                icon="el-icon-map-location"
              >Map
              </el-button>
            </router-link>
          </span>
        </div>
      </div> -->
      <vs-divider />
    </div>
    <div>
      <el-button
        :loading="downloading"
        round
        class="filter-item"
        type="primary"
        icon="el-icon-download"
        @click="handleDownload"
      >Export</el-button>
      <v-client-table
        v-model="list"
        :columns="columns"
        :options="options"
      >

        <template slot="bull" slot-scope="{row}">
          <div v-for="(rep, index) in row.reps" :key="index">
            <strong v-if="rep.member_of_team.team.name === 'Bull'"><i class="el-icon-user-solid" /> {{ rep.name }}<br></strong>
          </div>
        </template>
        <template slot="eagle" slot-scope="{row}">
          <div v-for="(rep, index) in row.reps" :key="index">
            <strong v-if="rep.member_of_team.team.name === 'Eagle'"><i class="el-icon-user-solid" /> {{ rep.name }}<br></strong>
          </div>
        </template>
        <template slot="lion" slot-scope="{row}">
          <div v-for="(rep, index) in row.reps" :key="index">
            <strong v-if="rep.member_of_team.team.name === 'Lion'"><i class="el-icon-user-solid" /> {{ rep.name }}<br></strong>
          </div>
        </template>
        <template slot="created_at" slot-scope="scope">
          <span>{{ moment(scope.row.created_at).format('ll') }}</span>
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
const customersResource = new Resource('customers/team-relationship');
export default {
  name: 'Customers',
  components: { Pagination, VueGoogleAutocomplete, FilterOptions },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      list: [],
      columns: [
        'business_name',
        'code',
        'customer_type.name',
        'bull',
        'eagle',
        'lion',
        'address',
        'created_at',
      ],

      options: {
        headings: {
          business_name: 'Name',
          'customer_type.name': 'Type',
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
        sortable: ['business_name', 'code', 'created_at'],
        filterable: ['business_name', 'code', 'customer_type.name'],
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
    };
  },
  created() {
    this.getList();
    // this.fetchSalesRep();
  },
  methods: {
    moment,
    checkPermission,
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
    //   if (confirm('You can only export the current view')) {
    //     this.export(this.list);
    //   }
      // fetch all data for export
      this.query.limit = this.total;
      this.query.paginate_option = 'all';
      this.downloading = true;
      customersResource.list(this.query)
        .then(response => {
          this.export(response.customers);

          this.downloading = false;
        });
    },
    export(export_data) {
      import('@/vendor/Export2Excel').then((excel) => {
        const tHeader = [
          'BUSINESS NAME',
          'CODE',
          'TYPE',
          'BULL',
          'EAGLE',
          'LION',
          'ADDRESS',
          'DATE REGISTERED',
        ];
        const filterVal = this.columns;
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
          if (j === 'bull') {
            let val = '';
            v['reps'].forEach(rep => {
              if (rep.member_of_team !== null) {
                if (rep.member_of_team.team.name === 'Bull') {
                  val += rep.name + ', ';
                }
              }
            });
            return val;
          }
          if (j === 'eagle') {
            let val = '';
            v['reps'].forEach(rep => {
              if (rep.member_of_team !== null) {
                if (rep.member_of_team.team.name === 'Eagle') {
                  val += rep.name + ', ';
                }
              }
            });
            return val;
          }
          if (j === 'lion') {
            let val = '';
            v['reps'].forEach(rep => {
              if (rep.member_of_team !== null) {
                if (rep.member_of_team.team.name === 'Lion') {
                  val += rep.name + ', ';
                }
              }
            });
            return val;
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
