<template>

  <div v-loading="load">
    <div slot="header" class="clearfix">
      <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
      <strong class="font-medium text-lg">Collections {{ sub_title }}</strong>
      <span style="float: right">
        <el-button
          :loading="downloadLoading"
          round
          style="margin:0 0 20px 20px;"
          type="success"
          icon="el-icon-download"
          size="small"
          @click="handleDownload"
        >Export Excel</el-button>
      </span>
    </div>
    <el-row :gutter="10">
      <el-col :lg="12" :md="12" :sm="12" :xs="24">
        <label for="">Select Customer</label>
        <el-select v-model="form.customer_id" style="width: 100%" @input="fetchPayments">
          <el-option
            label="All"
            value="all" />
          <el-option
            v-for="(customer, index) in customers"
            :key="index"
            :label="customer.business_name"
            :value="customer.id"

          />
        </el-select>
      </el-col>
      <el-col :lg="12" :md="12" :sm="12" :xs="24">
        <label for="">&nbsp;</label><br>
        <el-popover placement="right" trigger="click">
          <date-range-picker
            :from="$route.query.from"
            :to="$route.query.to"
            :panel="panel"
            :panels="panels"
            :submit-title="submitTitle"
            :future="future"
            @update="setDateRange"
          />
          <el-button id="pick_date2" slot="reference" type="primary">
            <i class="el-icon-date" /> Pick Date Range
          </el-button>
        </el-popover>
      </el-col>
    </el-row>
    <el-row :gutter="10">
      <v-client-table v-model="payments" :columns="payments_columns" :options="payments_options">
        <div
          slot="confirmer.name"
          slot-scope="{row}"
        >{{ (row.confirmer) ? row.confirmer.name : 'Not Confirmed' }}</div>
        <div
          slot="amount"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.amount).toLocaleString() }}</div>
        <div
          slot="payment_date"
          slot-scope="props"
        >{{ moment(props.row.payment_date).format('lll') }}</div>
        <div
          slot="created_at"
          slot-scope="props"
        >{{ moment(props.row.created_at).format('lll') }}</div>
        <div
          slot="updated_at"
          slot-scope="{row}"
        >{{ (row.confirmer) ? moment(row.updated_at).format('lll') : 'Pending' }}</div>
        <div
          slot="action"
          slot-scope="props"
        ><el-button v-if="props.row.confirmer === null && checkPermission(['confirm-payments'])" round type="success" size="small" @click="confirmPayment(props.index, props.row)">Confirm</el-button></div>

      </v-client-table>
    </el-row>
    <el-row :gutter="20">
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="form.page"
        :limit.sync="form.limit"
        @pagination="fetchPayments"
      />
    </el-row>
  </div>
</template>
<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
import checkPermission from '@/utils/permission'; // Permission checking
export default {
  components: { Pagination },
  props: {
    customers: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      payments: [],
      payments_columns: [
        'customer.business_name',
        'confirmer.name',
        'amount',
        'payment_date',
        'payment_type',
        'updated_at',
        'customer.assigned_officer.name',
        'action',
      ],

      payments_options: {
        headings: {
          'customer.business_name': 'Customer',
          'confirmer.name': 'Confirmed By',
          updated_at: 'Confirmed at',
          'customer.assigned_officer.name': 'Relating Officer',
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
        sortable: ['created_at'],
        filterable: ['customer.business_name'],
      },
      load: false,
      total: 0,
      currency: '',
      form: {
        from: '',
        to: '',
        panel: '',
        status: 'pending',
        page: 1,
        limit: 10,
        customer_id: 'all',
      },
      sub_title: '',
      submitTitle: 'Fetch Report',
      panel: 'month',
      future: false,
      panels: ['range', 'week', 'month', 'quarter', 'year'],
      show_calendar: false,
      downloadLoading: false,
    };
  },
  created() {
    this.fetchPayments();
  },
  methods: {
    moment,
    checkPermission,
    format(date) {
      var month = date.toLocaleString('en-US', { month: 'short' });
      return month + ' ' + date.getDate() + ', ' + date.getFullYear();
    },
    setDateRange(values) {
      const app = this;
      document.getElementById('pick_date2').click();
      app.show_calendar = false;
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
      app.fetchPayments();
    },
    fetchPayments() {
      const app = this;
      const { limit, page } = app.form;
      app.payments_options.perPage = limit;
      const paymentsResource = new Resource('payments');
      const param = app.form;
      paymentsResource.list(param)
        .then(response => {
          app.payments = response.payments.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.payments.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.payments.total;
        });
    },
    confirmPayment(index, row) {
      const app = this;
      app.$confirm('Are you sure you want to confirm this payment? It cannot be undone', 'Warning', {
        confirmButtonText: 'Yes Confirm',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        const confirmPaymentsResource = new Resource('payments/confirm');
        const param = app.form;
        app.load = true;
        confirmPaymentsResource.update(row.id, param)
          .then(response => {
            app.payments[index - 1].confirmer = response.payment.confirmer;
            app.payments[index - 1].updated_at = response.payment.updated_at;
            app.load = false;
          });
      }).catch(() => {});
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Collections ' + this.sub_title, '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'CONFIRMED BY',
          'AMOUNT',
          'PAYMENT DATE',
          'PAYMENT TYPE',
          'CONFIRMED AT',
          'RELATING OFFICER',
        ];
        const filterVal = this.payments_columns;
        const list = this.payments;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Collections',
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v =>
        filterVal.map(j => {
          if (j === 'payment_date') {
            return (v[j]) ? moment(v[j]).format('lll') : '';
          }
          if (j === 'updated_at') {
            return (v[j]) ? moment(v[j]).format('lll') : '';
          }
          if (j === 'customer.business_name') {
            return v['customer']['business_name'];
          }
          if (j === 'confirmer.name') {
            return (v['confirmer']) ? v['confirmer']['name'] : '';
          }
          if (j === 'customer.assigned_officer.name') {
            return (v['customer']) ? v['customer']['assigned_officer']['name'] : '';
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
