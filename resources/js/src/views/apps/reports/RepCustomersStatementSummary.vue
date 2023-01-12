<template>
  <el-card>

    <div class="no-print">
      <filter-options :hide-customers-list="true" @submitQuery="setParam" />
      <hr>
    </div>

    <div v-if="pageOption === 'drilldown'">
      <drill-down-customer-statement :customer-id="selectedCustomerId" :query="query" @back="pageOption = 'list'" />
    </div>
    <div v-else>
      <div v-if="customers.length > 0">

        <div class="no-print">
          <el-row :gutter="10">
            <!-- <el-col :xs="24" :sm="12" :md="12">
                <el-popover
                placement="right"
                trigger="click"
                >
                <date-range-picker :from="$route.query.from" :to="$route.query.to" :panel="panel" :panels="panels" :submit-title="submitTitle" :future="future" @update="setDateRange" />
                <el-button id="pick_customer_date" slot="reference" type="success">
                    <i class="el-icon-date" /> Pick Date Range
                </el-button>
                </el-popover>
            </el-col> -->
            <el-col :xs="24" :sm="24" :md="24">
              <div class="no-print">
                <div class="box-header">
                  <span class="pull-right">
                    <el-button :loading="downloadLoading" round type="primary" icon="document" @click="handleDownload">
                      Export Excel
                    </el-button>
                    <a class="btn btn-warning" @click="printCard()">
                      <i class="el-icon-printer" /> Print
                    </a>
                  </span>
                </div>
              </div>
            </el-col>
          </el-row>
        </div>
        <div class="print-padded">
          <div class="col-xs-12 page-header" align="center">
            <img src="/images/logo.png" alt="Company Logo" width="60">
            <span>
              <!-- <label>{{ params.company_name }}</label> -->
            </span>
            <h3><div class="label label-primary">Rep's Customers Transaction Summary</div></h3>
            <label>{{ table_title }}</label>
          </div>
          <!-- <div class="invoice-info">
            Description of Article: <label>{{ product_name }}</label>
            </div> -->
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th rowspan="2">CUSTOMER</th>
                <th rowspan="2">OPENING BAL (NGN)</th>
                <th colspan="2"><div align="center">TRANSACTION</div></th>
                <th rowspan="2">CLOSING BAL (NGN)</th>
                <!-- <th>REMARK</th> -->
              </tr>
              <tr>
                <th>DEBTS (NGN)</th>
                <th>COLLECTIONS (NGN)</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(customer, index) in customers">
                <tr :key="index" style="cursor: pointer" @click="drillDown(customer.id)">
                  <td>{{ customer.business_name }}</td>
                  <td ><strong>{{ Number(customer.opening_balance).toLocaleString() }}</strong></td>
                  <td style="color: red"><span>{{ Number(customer.debt).toLocaleString() }}</span></td>
                  <td style="color: green"><span>{{ Number(customer.collections).toLocaleString() }}</span></td>
                  <td><strong>{{ customer.closing_balance }}</strong></td>
                <!-- <td>{{ customer.balance }}</td> -->
                <!-- <td>{{ customer.remark }}</td> -->
                </tr>
              </template>

            </tbody>
          </table>

        </div>

      </div>
    </div>
  </el-card>
</template>
<script>
import moment from 'moment';
import Resource from '@/api/resource';
import FilterOptions from '@/views/apps/reports/FilterOptions';
import DrillDownCustomerStatement from './partials/DrillDownCustomerStatement';
// const deleteItemInStock = new Resource('stock/items-in-stock/delete');
export default {
  components: { FilterOptions, DrillDownCustomerStatement },
  data() {
    return {
      customers: [],
      currency: '',
      columns: ['business_name', 'opening_balance', 'debt', 'collections', 'closing_balance'],

      product_name: '',
      pageOption: 'list',
      query: {
        from: '',
        to: '',
        rep_id: '',
        team_id: '',
        date: '',
      },
      submitTitle: 'Fetch Report',
      panel: 'month',
      future: false,
      panels: ['range', 'week', 'month', 'quarter', 'year'],
      show_calendar: false,
      table_title: '',
      in_warehouse: '',
      selected_row_index: '',
      downloadLoading: false,
      running_total_array: [],
      packaging: '',
      selectedCustomerId: null,
    };
  },
  computed: {
    params() {
      return this.$store.getters.params;
    },
  },
  beforeDestroy() {

  },
  methods: {
    moment,
    drillDown(customerId) {
      this.selectedCustomerId = customerId;
      this.pageOption = 'drilldown';
    },
    printCard(){
      window.print();
    },
    showCalendar(){
      this.show_calendar = !this.show_calendar;
    },
    format(date) {
      var month = date.toLocaleString('en-US', { month: 'short' });
      return month + ' ' + date.getDate() + ', ' + date.getFullYear();
    },
    setParam(param) {
      this.query.from = param.from;
      this.query.to = param.to;
      this.query.rep_id = param.rep_id;
      this.query.team_id = param.team_id;
      this.query.date = param.date;
      this.pageOption = 'list';
      this.getCustomerStatement();
    },
    getCustomerStatement() {
      const app = this;
      const customerReport = new Resource('reports/rep-customer-statement-summary');
      customerReport.list(app.query)
        .then(response => {
          app.customers = response.customers;
          app.query.from = response.date_from_formatted;
          app.query.to = response.date_to_formatted;
          app.table_title = 'from ' + app.moment(app.query.from).format('ll') + ' to ' + app.moment(app.query.to).format('ll');
        })
        .catch(error => {
          // loader.hide();
          console.log(error.message);
        });
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Customers Transaction Summary ' + this.table_title, '', '', '', '', '']];
        const tHeader = ['CUSTOMER', 'OPENING BAL (NGN)', 'DEBT (NGN)', 'COLLECTIONS (NGN)', 'CLOSING BAL (NGN)'];
        const filterVal = this.columns;
        const list = this.customers;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Customers Transaction Summary ' + this.table_title,
          autoWidth: true,
          bookType: 'xlsx',
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v => filterVal.map(j => {
        if (j === 'date') {
          return moment(v['date']).format('ll');
        }
        return v[j];
      }));
    },
  },
};
</script>
<style>
  @media print {
    .print-padded{
      margin-top: -60px;

    }
    .table > thead > tr > th,
    .table > tbody > tr > th,
    .table > tfoot > tr > th,
    .table > thead > tr > td,
    .table > tbody > tr > td,
    .table > tfoot > tr > td {
      font-size: 1.1rem !important;
    }
  }
</style>
