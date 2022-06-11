<template>
  <el-card>
    <div class="no-print">
      <el-row :gutter="10">
        <el-col :xs="24" :sm="12" :md="12">
          <el-popover
            placement="right"
            trigger="click"
          >
            <date-range-picker :from="$route.query.from" :to="$route.query.to" :panel="panel" :panels="panels" :submit-title="submitTitle" :future="future" @update="setDateRange" />
            <el-button id="pick_statement_date" slot="reference" type="success">
              <i class="el-icon-date" /> Pick Date Range
            </el-button>
          </el-popover>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12">
          <div class="no-print">
            <div class="box-header">
              <span class="pull-right">
                <router-link
                  :to="'/customers/index'"
                >
                  <el-button
                    round
                    class="filter-item"
                    size="small"
                    type="danger"
                    icon="el-icon-back"
                  />
                </router-link>
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

    <div v-if="statements.length > 0">
      <div class="print-padded">
        <div class="col-xs-12 page-header" align="center">
          <img src="/images/logo.png" alt="Company Logo" width="80">
          <span>
            <!-- <label>{{ params.company_name }}</label> -->
          </span>
          <h3><div class="label label-primary">Customer Statement</div></h3>
          <label>{{ table_title }}</label>
        </div>
        <!-- <div class="invoice-info">
          Description of Article: <label>{{ product_name }}</label>
        </div> -->
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>DATE</th>
              <th>Description</th>
              <th>DEBT (NGN)</th>
              <th>PAID (NGN)</th>
              <!-- <th>BALANCE</th> -->
              <!-- <th>REMARK</th> -->
            </tr>
          </thead>
          <tbody>

            <tr v-for="(statement, index) in statements" :key="index">
              <td>{{ moment(statement.date).format('ll') }}</td>
              <td>{{ statement.description }}</td>
              <td v-if="statement.remark === 'opening' || statement.remark === 'closing'" style="background: #EA5455; color: #ffffff"><strong>{{ statement.debt }}</strong></td>
              <td v-else-if="statement.remark === 'total'" style="background: #000 ; color: #ffffff">{{ statement.debt }}</td>
              <td v-else style="background: #EA5455 ; color: #ffffff">{{ statement.debt }}</td>

              <td v-if="statement.remark === 'opening' || statement.remark === 'closing'" style="background: #28C76F ; color: #ffffff"><strong>{{ statement.paid }}</strong></td>
              <td v-else-if="statement.remark === 'total'" style="background: #000 ; color: #ffffff">{{ statement.paid }}</td>
              <td v-else style="background: #28C76F ; color: #ffffff">{{ statement.paid }}</td>
              <!-- <td>{{ statement.balance }}</td> -->
              <!-- <td>{{ statement.remark }}</td> -->
            </tr>
          </tbody>
        </table>

      </div>

    </div>
  </el-card>
</template>
<script>
import moment from 'moment';
import Resource from '@/api/resource';
const statementReport = new Resource('reports/customer-statement');
// const deleteItemInStock = new Resource('stock/items-in-stock/delete');
export default {
  name: 'CustomerStatement',
  data() {
    return {
      running_balance: 0,
      brought_forward: 0,
      warehouses: [],
      items: [],
      statements: [],
      currency: '',
      columns: ['date', 'description', 'debt', 'paid'],

      product_name: '',
      page: {
        option: 'list',
      },
      form: {
        from: '',
        to: '',
        panel: '',
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
    };
  },
  computed: {
    params() {
      return this.$store.getters.params;
    },
  },
  mounted() {
    this.getstatement();
  },
  beforeDestroy() {

  },
  methods: {
    moment,
    printCard(){
      window.print();
    },
    showCalendar(){
      this.show_calendar = !this.show_calendar;
    },
    setRunningBal(quantity_transacted, type, index) {
      const app = this;
      // var running_balance = app.running_balance;
      if (type === 'in_bound') {
        app.running_balance += parseInt(quantity_transacted);
      } else {
        app.running_balance -= parseInt(quantity_transacted);
      }
      app.statements[index].balance = app.running_balance;
    },
    // fetchNecessaryParams() {
    //   const app = this;
    //   necessaryParams.list().then((response) => {
    //     const params = response.params;
    //     app.$store.dispatch('app/setNecessaryParams', params);
    //     app.warehouses = params.warehouses;
    //     app.form.warehouse_id = params.warehouses[0].id;
    //     app.form.warehouse_index = 0;
    //     app.items = params.items;
    //     app.currency = params.currency;
    //   });
    // },
    format(date) {
      var month = date.toLocaleString('en-US', { month: 'short' });
      return month + ' ' + date.getDate() + ', ' + date.getFullYear();
    },
    setDateRange(values){
      const app = this;
      document.getElementById('pick_statement_date').click();
      let panel = app.panel;
      let from = app.week_start;
      let to = app.week_end;
      if (values !== '') {
        to = this.format(new Date(values.to));
        from = this.format(new Date(values.from));
        panel = values.panel;
      }
      app.form.from = from;
      app.form.to = to;
      app.form.panel = panel;
      app.getstatement();
    },
    getstatement() {
      const app = this;
      // const loader = statementReport.loaderShow();
      const id = this.$route.params && this.$route.params.id;
      const param = app.form;
      param.customer_id = id;
      statementReport.list(param)
        .then(response => {
          app.statements = response.statements;
          app.brought_forward = response.brought_forward;
          app.running_balance = app.brought_forward;
          app.form.from = response.date_from_formatted;
          app.form.to = response.date_to_formatted;
          app.table_title = response.customer.business_name + ' from ' + app.moment(app.form.from).format('ll') + ' to ' + app.moment(app.form.to).format('ll');
          // loader.hide();
          app.setRunningTotalArray(app.statements);
        })
        .catch(error => {
          // loader.hide();
          console.log(error.message);
        });
    },
    setRunningTotalArray(statements){
      const app = this;
      let total_paid = 0;
      let total_debt = app.brought_forward;
      for (let index = 0; index < statements.length; index++) {
        const each_entry = statements[index];
        if (each_entry.type === 'paid') {
          total_paid += each_entry.amount_transacted;
        } else {
          total_debt += each_entry.amount_transacted;
        }
      }
      app.statements.unshift(
        {
          'type': 'debt',
          'date': app.moment(app.form.from).format('ll'),
          'description': 'Opening Balance',
          'debt': Number(app.brought_forward).toLocaleString(),
          'paid': '',
          // 'balance': '',
          'remark': 'opening',
        },
      );
      const balance = total_debt - total_paid;
      app.statements.push(
        {
          'type': 'debt',
          'date': app.moment(app.form.to).format('ll'),
          'description': 'Running Total',
          'debt': (total_debt > 0) ? Number(total_debt).toLocaleString() : '',
          'paid': (total_paid > 0) ? Number(total_paid).toLocaleString() : '',
          // 'balance': '',
          'remark': 'total',
        },
        {
          'type': 'debt',
          'date': app.moment(app.form.to).format('ll'),
          'description': 'Closing Balance',
          'debt': Number(balance).toLocaleString(),
          'paid': '',
          // 'balance': balance,
          'remark': 'closing',
        },
      );
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Statement for ' + this.table_title, '', '', '', '']];
        const tHeader = ['DATE', 'DESCRIPTION', 'DEBT', 'PAID'];
        const filterVal = this.columns;
        const list = this.statements;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'statement for ' + this.table_title,
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
