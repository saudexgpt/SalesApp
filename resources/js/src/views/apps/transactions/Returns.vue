<template>

  <div v-loading="load">
    <div slot="header" class="clearfix">
      <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
      <strong class="font-medium text-lg">Returned Products {{ sub_title }}</strong>
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
          <el-button id="pick_date3" slot="reference" type="primary">
            <i class="el-icon-date" /> Pick Date Range
          </el-button>
        </el-popover>
      </el-col>
    </el-row>
    <el-row :gutter="10">
      <v-client-table v-model="returns" :columns="returns_columns" :options="returns_options">
        <div
          slot="quantity"
          slot-scope="props"
        >{{ props.row.quantity + ' ' + props.row.item.package_type }}</div>
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
          slot="expiry_date"
          slot-scope="props"
        >{{ moment(props.row.expiry_date).format('ll') }}</div>
        <div
          slot="created_at"
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
      returns: [],
      returns_columns: [
        'customer.business_name',
        'item.name',
        'quantity',
        'rate',
        'amount',
        'batch_no',
        'expiry_date',
        'reason',
        'rep.name', // field staff
        'created_at',
      ],

      returns_options: {
        headings: {
          'customer.business_name': 'Customer',
          'item.name': 'Product',
          'rep.name': 'Field Staff',
          'created_at': 'Created at',
          batch_no: 'Batch',
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
        sortable: ['item.name', 'customer.business_name', 'expiry_date', 'created_at', 'rep.name,'],
        filterable: ['item.name', 'customer.business_name', 'expiry_date', 'created_at', 'rep.name'],
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
      document.getElementById('pick_date3').click();
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
      app.returns_options.perPage = limit;
      const returnsResource = new Resource('reports/fetch-returned-products');
      const param = app.form;
      app.load = true;
      returnsResource.list(param)
        .then(response => {
          app.returns = response.returns.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          app.returns.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.returns.total;
          app.load = false;
        });
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Product Returned ' + this.sub_title, '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'PRODUCT',
          'QUANTITY',
          'RATE',
          'AMOUNT',
          'BATCH NO.',
          'EXPIRY DATE',
          'REASON',
          'FIELD STAFF',
          'CREATED AT',
        ];
        const filterVal = this.returns_columns;
        const list = this.returns;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Returned Products',
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
          if (j === 'created_at') {
            return (v[j]) ? moment(v[j]).format('lll') : '';
          }
          if (j === 'customer.business_name') {
            return v['customer']['business_name'];
          }
          if (j === 'rep.name') {
            return v['rep']['name'];
          }
          if (j === 'quantity') {
            return v['quantity'] + ' ' + v['item']['package_type'];
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
