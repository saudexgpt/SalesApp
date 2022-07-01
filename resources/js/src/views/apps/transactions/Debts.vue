<template>

  <vx-card>
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="CreditCardIcon" class="mr-2" />
          <span class="font-medium text-lg">Debts {{ sub_title }}</span>
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
    <filter-options @submitQuery="fetchDebts" />
    <el-row v-loading="load" :gutter="10">
      <v-client-table v-model="debts" :columns="debts_columns" :options="debts_options">
        <!-- <div slot="child_row" slot-scope="props" style="background: #000">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Rate</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>

              <tr v-for="(detail, index) in props.row.details" :key="index">

                <td>{{ detail.product }}</td>
                <td>{{ detail.quantity }}</td>
                <td>{{ detail.rate }}</td>
                <td>{{ currency + Number(detail.amount).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div> -->

        <div
          slot="relating_officer"
          slot-scope="props"
          class="alert alert-info"
        >{{ (props.row.customer.relating_officer) ? props.row.customer.assigned_officer.name : '' }}</div>
        <div
          slot="total_amount_due"
          slot-scope="props"
          class="alert alert-info"
        >{{ currency + Number(props.row.total_amount_due).toLocaleString() }}</div>
        <div
          slot="total_amount_paid"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.total_amount_paid).toLocaleString() }}</div>
        <div
          slot="balance"
          slot-scope="props"
          class="alert alert-danger"
        >{{ currency + Number(props.row.total_amount_due - props.row.total_amount_paid).toLocaleString() }}</div>
        <div
          slot="created_at"
          slot-scope="props"
        >{{ moment(props.row.created_at).fromNow() }}</div>
        <div
          slot="date"
          slot-scope="props"
        >{{ moment(props.row.created_at).format('lll') }}</div>
      </v-client-table>
    </el-row>
    <el-row :gutter="20">
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="form.page"
        :limit.sync="form.limit"
        @pagination="fetchDebts"
      />
    </el-row>
  </vx-card>
</template>
<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
import FilterOptions from '@/views/apps/reports/FilterOptions';
export default {
  components: { Pagination, FilterOptions },
  data() {
    return {
      debts: [],
      debts_columns: [
        'customer.business_name',
        // 'invoice_no',
        'total_amount_due',
        'total_amount_paid',
        'balance',
        'relating_officer',

        // 'delivery_status',
        'date',
        'created_at',
      ],

      debts_options: {
        headings: {
          'customer.business_name': 'Customer',
          // invoice_no: 'Invoice No.',
          total_amount_due: 'Due',
          total_amount_paid: 'Paid',
          delivery_status: 'Delivery Status',
          relating_officer: 'Relating Officer',
          created_at: 'Age',
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
      reps: [],
      customers: [],
    };
  },
  created() {
    // this.fetchSalesReps();
    // this.fetchDebts();
  },
  methods: {
    moment,
    fetchDebts(param) {
      const app = this;
      app.form = param;
      const { limit, page } = app.form;
      app.debts_options.perPage = limit;
      const debtsResource = new Resource('sales/fetch-debts');
      app.load = true;
      debtsResource.list(param)
        .then(response => {
          app.load = false;
          app.debts = response.debts.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.debts.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.debts.total;
        });
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Customer Debts ' + this.sub_title, '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'AMOUNT',
          'AMOUNT PAID',
          'BALANCE',
          'RELATING OFFICER',
          'DATE',
          'AGE',
        ];
        const filterVal = this.debts_columns;
        const list = this.debts;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Debts',
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v =>
        filterVal.map(j => {
          if (j === 'customer.business_name') {
            return v['customer']['business_name'];
          }
          if (j === 'relating_officer') {
            return (v['customer']['assigned_officer']) ? v['customer']['assigned_officer']['name'] : '';
          }
          if (j === 'created_at') {
            return moment(v['created_at']).fromNow();
          }
          if (j === 'balance') {
            return v['total_amount_due'] - v['total_amount_paid'];
          }
          if (j === 'date') {
            return moment(v['created_at']).format('lll');
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
