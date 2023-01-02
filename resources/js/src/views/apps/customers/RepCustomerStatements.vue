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

                <el-button
                  round
                  class="filter-item"
                  size="small"
                  type="danger"
                  icon="el-icon-back"
                  @click="$emit('back');"
                />
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
              <th rowspan="2">DATE</th>
              <th rowspan="2">DESCRIPTION</th>
              <th rowspan="2">OPENING BAL (NGN)</th>
              <th colspan="2"><div align="center">TRANSACTION</div></th>
              <th rowspan="2">CLOSING BAL (NGN)</th>
              <!-- <th>REMARK</th> -->
            </tr>
            <tr>
              <th>SALES (NGN)</th>
              <th>COLLECTION (NGN)</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(statement, index) in statements">
              <template v-if="statement.remark === 'total'">

                <tr v-if="statement.paid !== '' || statement.debt !== ''" :key="index">
                  <td>{{ moment(statement.date).format('ll') }}</td>
                  <td>{{ statement.description }}</td>
                  <td ><strong v-if="statement.remark === 'opening'">{{ statement.opening_bal }}</strong></td>
                  <td :style="'color: '+ statement.color"><span v-if="statement.remark !== 'opening' && statement.remark !== 'closing'">{{ (statement.debt != '') ? Number(statement.debt).toLocaleString() : '' }}</span></td>
                  <td :style="'color: '+ statement.color"><span v-if="statement.remark !== 'opening' && statement.remark !== 'closing'">{{ (statement.paid != '') ? Number(statement.paid).toLocaleString() : '' }}</span></td>
                  <td><strong v-if="statement.remark === 'closing'">{{ statement.closing_bal }}</strong></td>
                  <!-- <td>{{ statement.balance }}</td> -->
                  <!-- <td>{{ statement.remark }}</td> -->
                </tr>
              </template>
              <tr v-else :key="index">
                <td>{{ moment(statement.date).format('ll') }}</td>
                <td>{{ statement.description }}</td>
                <td ><strong v-if="statement.remark === 'opening'">{{ statement.opening_bal }}</strong></td>
                <td :style="'color: '+ statement.color"><span v-if="statement.remark !== 'opening' && statement.remark !== 'closing'">{{ (statement.debt != '') ? Number(statement.debt).toLocaleString() : '' }}</span></td>
                <td :style="'color: '+ statement.color"><span v-if="statement.remark !== 'opening' && statement.remark !== 'closing'">{{ (statement.paid != '') ? Number(statement.paid).toLocaleString() : '' }}</span></td>
                <td><strong v-if="statement.remark === 'closing'">{{ statement.closing_bal }}</strong></td>
              <!-- <td>{{ statement.balance }}</td> -->
              <!-- <td>{{ statement.remark }}</td> -->
              </tr>
            </template>
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
  props: {
    customerId: {
      type: Number,
      default: null,
    },
    repId: {
      type: Number,
      default: null,
    },
  },
  data() {
    return {
      running_balance: 0,
      brought_forward: 0,
      warehouses: [],
      items: [],
      statements: [],
      currency: '',
      columns: ['date', 'description', 'opening_bal', 'debt', 'paid', 'closing_bal'],

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
      const param = app.form;
      param.customer_id = this.customerId;
      param.rep_id = this.repId;
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
          'description': '',
          'opening_bal': Number(app.brought_forward).toLocaleString(),
          'closing_bal': '',
          'debt': '',
          'paid': '',
          'color': '#000000',
          // 'balance': '',
          'remark': 'opening',
        },
      );
      const balance = total_debt - total_paid;
      app.statements.push(
        {
          'type': 'debt',
          'date': app.moment(app.form.to).format('ll'),
          'description': 'Total',
          'opening_bal': '',
          'closing_bal': '',
          'debt': (total_debt > 0) ? total_debt : '',
          'paid': (total_paid > 0) ? total_paid : '',
          // 'balance': '',
          'color': '#EA5455',
          'remark': 'total',
        },
        {
          'type': 'debt',
          'date': app.moment(app.form.to).format('ll'),
          'description': '',
          'opening_bal': '',
          'closing_bal': Number(balance).toLocaleString(),
          'debt': '',
          'paid': '',
          'color': '#000000',
          // 'balance': balance,
          'remark': 'closing',
        },
      );
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Statement for ' + this.table_title, '', '', '', '', '']];
        const tHeader = ['DATE', 'DESCRIPTION', 'OPENING BAL (NGN)', 'SALES (NGN)', 'COLLECTIONS (NGN)', 'CLOSING BAL (NGN)'];
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
