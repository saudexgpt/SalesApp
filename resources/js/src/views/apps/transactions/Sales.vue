<template>

  <div v-loading="load">
    <div slot="header" class="clearfix">
      <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
      <strong class="font-medium text-lg">Sales {{ sub_title }}</strong>
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
        <div slot="child_row" slot-scope="props" style="background: #000">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Rate</th>
                <th>Amount</th>
              <!-- <th>Paid</th>
                    <th>Balance</th>
                    <th>Payment Due Date</th> -->
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
        </div>
        <div
          slot="amount_due"
          slot-scope="props"
          class="alert alert-info"
        >{{ currency + Number(props.row.amount_due).toLocaleString() }}</div>
        <div
          slot="amount_paid"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.amount_paid).toLocaleString() }}</div>
        <div
          slot="balance"
          slot-scope="props"
          class="alert alert-danger"
        >{{ currency + Number(props.row.amount_due - props.row.amount_paid).toLocaleString() }}</div>
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
        :page.sync="query.page"
        :limit.sync="query.limit"
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
        'customer.business_name',
        // 'invoice_no',
        'amount_due',
        'amount_paid',
        'balance',
        'delivery_status',
        'customer.assigned_officer.name',
        'created_at',
      ],

      sales_options: {
        headings: {
          'customer.business_name': 'Customer',
          // invoice_no: 'Invoice No.',
          amount_due: 'Amount',
          amount_paid: 'Amount Paid',
          delivery_status: 'Delivery Status',
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
      query: {
        page: 1,
        limit: 10,
        keyword: '',
        role: '',
      },
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
      const { limit, page } = app.query;
      app.sales_options.perPage = limit;
      const salesResource = new Resource('sales/fetch');
      const param = app.form;
      app.load = true;
      salesResource.list(param)
        .then(response => {
          app.sales = response.sales.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.sales.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.sales.total;
          app.load = false;
        });
    },
  },
};
</script>
