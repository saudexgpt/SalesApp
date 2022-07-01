<template>
  <vx-card>
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Sales {{ sub_title }}</span>
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
    <filter-options @submitQuery="fetchSales" />
    <el-row v-loading="load" :gutter="10">
      <v-client-table v-model="sales" :columns="sales_columns" :options="sales_options">
        <div
          slot="quantity"
          slot-scope="props"
        >{{ props.row.quantity + ' ' + props.row.packaging }}</div>
        <div
          slot="amount"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.amount).toLocaleString() }}</div>
        <div
          slot="rate"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.rate).toLocaleString() }}</div>
        <!-- <div
          slot="main_rate"
          slot-scope="props"
          class="alert alert-info"
        >{{ currency + Number(props.row.main_rate).toLocaleString() }}</div>
        <div
          slot="main_amount"
          slot-scope="props"
          class="alert alert-info"
        >{{ currency + Number(props.row.main_amount).toLocaleString() }}</div>
        <div
          slot="balance"
          slot-scope="props"
          class="alert alert-danger"
        >{{ currency + Number(props.row.amount_due - props.row.amount_paid).toLocaleString() }}</div>
        <div
          slot="expiry_date"
          slot-scope="props"
        >{{ (props.row.expiry_date) ? moment(props.row.expiry_date).format('ll') : '' }}</div> -->
        <div
          slot="transaction.created_at"
          slot-scope="props"
        >{{ moment(props.row.transaction.created_at).format('lll') }}</div>
      </v-client-table>
    </el-row>
    <el-row :gutter="20">
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="form.page"
        :limit.sync="form.limit"
        @pagination="fetchSales"
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
      sales: [],
      reps: [],
      sales_columns: [
        'transaction.customer.business_name',
        'transaction.invoice_no',
        'product',
        'quantity',
        // 'main_rate',
        // 'main_amount',
        'rate',
        'amount',
        // 'batch_no',
        // 'expiry_date',
        'name', // field staff
        'transaction.created_at',
      ],

      sales_options: {
        headings: {
          'transaction.customer.business_name': 'Customer Name',
          'transaction.invoice_no': 'Invoice No.',
          amount_due: 'Amount',
          amount_paid: 'Amount Paid',
          delivery_status: 'Delivery Status',
          'name': 'Field Staff',
          //   'rate': 'Sell Rate',
          //   'amount': 'Sell Amount',
          //   'main_amount': 'Original Amount',
          //   'main_rate': 'Original Rate',
          'transaction.created_at': 'Date',
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
        sortable: ['transaction.invoice_no', 'product', 'transaction.created_at', 'name,'],
        filterable: ['transaction.invoice_no', 'product', 'transaction.customer.business_name', 'transaction.created_at', 'name'],
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
        limit: 25,
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
    };
  },
  created() {
  },
  methods: {
    moment,
    fetchSales(param) {
      const app = this;
      app.form = param;
      const { limit, page } = param;
      app.sales_options.perPage = limit;
      const salesResource = new Resource('sales/fetch-product-sales');
      app.load = true;
      salesResource.list(param)
        .then(response => {
          app.sales = response.sales.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          app.sales.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.sales.total;
          app.load = false;
        });
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Product Sales ' + this.sub_title, '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'INVOICE NUMBER',
          'PRODUCT',
          'QUANTITY',
          //   'ORIGINAL RATE',
          //   'ORIGINAL AMOUNT',
          'RATE',
          'AMOUNT',
          // 'BATCH NO.',
          // 'EXPIRY DATE',
          'FIELD STAFF',
          'DATE',
        ];
        const filterVal = this.sales_columns;
        const list = this.sales;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Product Sales',
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v =>
        filterVal.map(j => {
          if (j === 'expiry_date') {
            moment(v['expiry_date']).format('lll');
          }
          if (j === 'transaction.created_at') {
            return (v['transaction']) ? moment(v['transaction']['created_at']).format('lll') : '';
          }
          if (j === 'transaction.customer.business_name') {
            return v['transaction']['customer']['business_name'];
          }
          if (j === 'transaction.invoice_no') {
            return v['transaction']['invoice_no'];
          }
          if (j === 'quantity') {
            return v['quantity'] + ' ' + v['packaging'];
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
