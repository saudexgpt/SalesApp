<template>

  <div v-loading="load">
    <div slot="header" class="clearfix">
      <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
      <strong class="font-medium text-lg">Sales {{ sub_title }}</strong>
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
        <el-select v-model="form.customer_id" style="width: 100%" @input="fetchSales">
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
          <el-button id="pick_date" slot="reference" type="primary">
            <i class="el-icon-date" /> Pick Date Range
          </el-button>
        </el-popover>
      </el-col>
    </el-row>
    <el-row :gutter="10">
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
        <div
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
        >{{ moment(props.row.expiry_date).format('ll') }}</div>
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
  </div>
</template>
<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
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
      sales: [],
      sales_columns: [
        'transaction.customer.business_name',
        'transaction.invoice_no',
        'product',
        'quantity',
        'main_rate',
        'main_amount',
        'rate',
        'amount',
        'batch_no',
        'expiry_date',
        'name', // field staff
        'transaction.created_at',
      ],

      sales_options: {
        headings: {
          'transaction.customer.business_name': 'Customer',
          'transaction.invoice_no': 'Invoice No.',
          amount_due: 'Amount',
          amount_paid: 'Amount Paid',
          delivery_status: 'Delivery Status',
          'name': 'Field Staff',
          'rate': 'Sell Rate',
          'amount': 'Sell Amount',
          'main_amount': 'Original Amount',
          'main_rate': 'Original Rate',
          'transaction.created_at': 'Created at',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 25,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['transaction.invoice_no', 'transaction.created_at', 'name,'],
        filterable: ['transaction.invoice_no', 'transaction.customer.business_name', 'transaction.created_at', 'name'],
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
    this.fetchSales();
  },
  methods: {
    moment,
    format(date) {
      var month = date.toLocaleString('en-US', { month: 'short' });
      return month + ' ' + date.getDate() + ', ' + date.getFullYear();
    },
    setDateRange(values) {
      const app = this;
      document.getElementById('pick_date').click();
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
      app.fetchSales();
    },
    fetchSales() {
      const app = this;
      const { limit, page } = app.form;
      app.sales_options.perPage = limit;
      const salesResource = new Resource('sales/fetch-product-sales');
      const param = app.form;
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
        const multiHeader = [['Product Sales ' + this.sub_title, '', '', '', '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'INVOICE NUMBER',
          'PRODUCT',
          'QUANTITY',
          'ORIGINAL RATE',
          'ORIGINAL AMOUNT',
          'SELL RATE',
          'SELL AMOUNT',
          'BATCH NO.',
          'EXPIRY DATE',
          'FIELD STAFF',
          'CREATED AT',
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
            return (v[j]) ? moment(v[j]).format('lll') : '';
          }
          if (j === 'transaction.created_at') {
            return (v[j]) ? moment(v[j]).format('lll') : '';
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
