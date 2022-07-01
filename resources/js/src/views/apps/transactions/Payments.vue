<template>

  <vx-card>
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="CreditCardIcon" class="mr-2" />
          <span class="font-medium text-lg">Collections {{ sub_title }}</span>
        </div>
        <vs-divider />
      </div>
      <div class="vx-col lg:w-1/4 w-full">
        <div class="flex items-end px-3">
          <span class="pull-right">
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
      </div>
    </div>
    <filter-options @submitQuery="fetchPayments" />
    <el-row v-loading="load" :gutter="10">
      <v-client-table v-model="payments" :columns="payments_columns" :options="payments_options">
        <div
          slot="confirmer.name"
          slot-scope="{row}"
        >{{ (row.confirmer) ? row.confirmer.name : 'Not Confirmed' }}</div>
        <div
          slot="total_amount"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.total_amount).toLocaleString() }}</div>
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
  </vx-card>
</template>
<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
import checkPermission from '@/utils/permission'; // Permission checking
import FilterOptions from '@/views/apps/reports/FilterOptions';
export default {
  components: { Pagination, FilterOptions },
  data() {
    return {
      payments: [],
      payments_columns: [
        'customer.business_name',
        'confirmer.name',
        'total_amount',
        'payment_date',
        'payment_type',
        'updated_at',
        'customer.assigned_officer.name',
        // 'action',
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
          chunk: 100,
        },
        perPage: 100,
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
        rep_id: '',
      },
      sub_title: '',
      submitTitle: 'Fetch Report',
      panel: 'month',
      future: false,
      panels: ['range', 'week', 'month', 'quarter', 'year'],
      show_calendar: false,
      downloadLoading: false,
      customers: [],
      reps: [],
    };
  },
  created() {
    // this.fetchSalesReps();
    // this.fetchCustomers();
    // this.fetchPayments();
  },
  methods: {
    moment,
    checkPermission,
    fetchPayments(param) {
      const app = this;
      app.form = param;
      const { limit, page } = app.form;
      app.payments_options.perPage = limit;
      const paymentsResource = new Resource('payments');
      app.load = true;
      paymentsResource.list(param)
        .then(response => {
          app.payments = response.payments.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.payments.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.payments.total;
          app.load = false;
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
          if (j === 'updated_at') {
            return (v[j]) ? moment(v['updated_at']).format('lll') : '';
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
