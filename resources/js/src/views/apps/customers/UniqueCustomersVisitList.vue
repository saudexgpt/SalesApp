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
      <filter-options :submit-on-rep-change="false" :hide-customers-list="true" :show-date-picker="true" @submitQuery="setParam" />
      <el-button
        :loading="downloading"
        round
        class="filter-item"
        type="primary"
        icon="el-icon-download"
        @click="handleDownload"
      >Export</el-button>
      <div v-if="!showStatement">

        <v-client-table
          v-model="list"
          :columns="columns"
          :options="options"
        >

          <template slot="visits" slot-scope="{row}">
            {{ row.visits.length }}
          </template>
          <template slot="frequency" slot-scope="{row}">
            <span v-for="(freq, index) in fetchVisitPurposes(row.visits)" :key="index">
              {{ index }}: {{ freq }}<br>
            </span>
          </template>
          <template slot="transactions" slot-scope="{row}">
            {{ calculateTotalSales(row.transactions) }}
          </template>
          <template slot="payments" slot-scope="{row}">
            {{ calculateTotalCollections(row.payments) }}
          </template>
          <template slot="created_at" slot-scope="scope">
            <span>{{ moment(scope.row.created_at).format('ll') }}</span>
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
const customersResource = new Resource('customers/rep-customers-with-unique-visits');
export default {
  name: 'Customers',
  components: { RepCustomerStatement, Pagination, VueGoogleAutocomplete, FilterOptions },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      list: [],
      columns: [
        'business_name',
        'code',
        'customer_type.name',
        'visits',
        'frequency',
        'transactions',
        'payments',
        'address',
      ],

      options: {
        headings: {
          business_name: 'Name',
          'customer_type.name': 'Type',
          transactions: 'Sales',
          payments: 'Collections',
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
        from: '',
        to: '',
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
      this.query.from = param.from;
      this.query.to = param.to;
      this.query.rep_id = param.rep_id;
      this.query.team_id = param.team_id;
      this.getList();
    },
    fetchVisitPurposes(visits) {
      const purposes = {};
      visits.forEach(visit => {
        purposes[visit.purpose] = (purposes[visit.purpose]) ? purposes[visit.purpose] += 1 : 1;
      });
      return purposes;
    },
    formatNumber(num, decimalPlace) {
      const dec_no = parseFloat(num).toFixed(decimalPlace);
      const formated_no = Number(dec_no).toLocaleString('en', { minimumFractionDigits: decimalPlace, maximumFractionDigits: decimalPlace });

      return formated_no;
    },
    calculateTotalCollections(payments) {
      let total = 0;
      payments.forEach(payment => {
        total += payment.amount;
      });
      return '₦' + this.formatNumber(total, 2);
    },
    calculateTotalSales(sales) {
      let total = 0;
      sales.forEach(sale => {
        total += sale.amount_due;
      });
      return '₦' + this.formatNumber(total, 2);
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
    handleFilter() {
      this.query.page = 1;
      this.getList();
    },
    handleDownload(){
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
          'VISITS',
          'FREQUENCY',
          'SALES',
          'COLLECTIONS',
          'ADDRESS',
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
      const app = this;
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'customer_type.name') {
            if (v['customer_type'] !== null) {
              return v['customer_type']['name'];
            }
          }
          if (j === 'visits') {
            return v['visits'].length;
          }
          if (j === 'frequency') {
            let str = '';
            const object = app.fetchVisitPurposes(v['visits']);
            for (const key in object) {
              if (Object.hasOwnProperty.call(object, key)) {
                const element = object[key];

                str += `${key}: ${element}`;
              }
            }
            return str;
          }
          if (j === 'transactions') {
            return app.calculateTotalSales(v['transactions']);
          }
          if (j === 'payments') {
            return app.calculateTotalCollections(v['payments']);
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
