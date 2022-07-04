<!-- =========================================================================================
  File Name: DashboardAnalytics.vue
  Description: Dashboard Analytics
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div v-loading="loading" id="dashboard-analytics">
    <data-analysis :dashboard-data="dashboardData" />
    <div class="vx-row">
      <div class="vx-col w-full">
        <transaction-chart />
      </div>
    </div>
    <!-- <el-card>
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <div class="flex staffs-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
            <strong class="font-medium text-lg">Debts Report {{ sub_title }}</strong>
          </div>
          <vs-divider />
        </div>
        <div class="vx-col lg:w-1/4 w-full">
          <div class="flex staffs-end px-3">
            <span class="pull-right">
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
            </span>
          </div>
        </div>
        <div v-loading="load_table" class="w-full">
          <v-client-table
            v-model="debts"
            :columns="columns"
            :options="options"
          >
            <template slot="amount_due" slot-scope="{row}">
              <div class="alert alert-info">{{ currency + row.total_amount_due.toLocaleString() }}</div>
            </template>
            <template slot="amount_paid" slot-scope="{row}">
              <div class="alert alert-success">{{ currency + row.total_amount_paid.toLocaleString() }}</div>
            </template>
            <template slot="balance" slot-scope="{row}">
              <div class="alert alert-danger">{{ currency + (row.total_amount_due - row.total_amount_paid).toLocaleString() }}</div>
            </template>
            <template slot="due_date" slot-scope="{row}">
              <div>{{ moment(row.due_date).format('ll') }}</div>
            </template>
            <template slot="entry_date" slot-scope="{row}">
              <div>{{ moment(row.entry_date).format('ll') }}</div>
            </template>
            <template slot="created_at" slot-scope="{row}">
              <div>{{ moment(row.created_at).format('ll') }}</div>
            </template>
          </v-client-table>
          <el-row :gutter="20">
            <pagination
              v-show="total > 0"
              :total="total"
              :page.sync="query.page"
              :limit.sync="query.limit"
              @pagination="fetchDebts"
            />
          </el-row>
        </div>
      </div>
    </el-card> -->
  </div>
</template>

<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import StatisticsCardLine from '@/components/statistics-cards/StatisticsCardLine.vue';
import TransactionChart from '@/views/dashboard/admin/partials/TransactionChart';
import DataAnalysis from './partials/DataAnalysis';
import Resource from '@/api/resource';
export default {
  components: {
    DataAnalysis,
    // VueApexCharts,
    StatisticsCardLine,
    TransactionChart,
    Pagination,
  },
  data() {
    return {
      user: '',
      dispatchedOrders: [],
      dashboardData: null,
      loading: false,
      debts: [],
      columns: [
        'customer.business_name',
        // 'invoice_no',
        'amount_due',
        'amount_paid',
        'balance',
        'staff.name',
        // 'entry_date',
        // 'created_at',
      ],

      options: {
        headings: {
          'customer.business_name': 'Customer',
          due_date: 'Payment Due Date',
          entry_date: 'Sales Date',
          'staff.name': 'Field Staff',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        filterByColumn: true,
        sortable: ['customer.business_name', 'staff.name'],
        filterable: ['customer.business_name', 'staff.name'],
      },
      form: {
        from: '',
        to: '',
        panel: '',
        status: 'pending',
        page: 1,
        limit: 10,
        keyword: '',
      },
      sub_title: '',
      submitTitle: 'Fetch Report',
      panel: 'month',
      future: false,
      panels: ['range', 'week', 'month', 'quarter', 'year'],
      show_calendar: false,
      total: 0,
      load_table: false,
      query: {
        page: 1,
        limit: 10,
        keyword: '',
        role: '',
      },
      currency: '',
    };
  },
  created() {
    this.user = this.$store.getters.name;
    this.fetchDashboard();
    this.fetchDebts();
  },
  methods: {
    moment,
    fetchDashboard(){
      const app = this;
      // this.$store.dispatch('dashboard/fetchDashboardData', 'debts-rep')
      // const fetchResource = new Resource('dashboard/debts-rep'); // enter the fetch customer url
      app.loading = true;
      const fetchResource = new Resource('dashboard/manager');
      fetchResource.list().then(response => {
        app.dashboardData = response;
        // app.$store.dispatch('orders/setTodayOrders', response.today_orders);
        // app.$store.dispatch('visits/setTodayVisits', response.today_visits);
        // app.$store.dispatch('schedules/setTodaySchedules', response.today_schedule);
        app.loading = false;
        // loader.hide();
      });
    },
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
      app.fetchDebts();
    },
    fetchDebts(){
      const app = this;
      const { limit, page } = app.query;
      app.options.perPage = limit;
      app.load_table = true;
      const param = app.form;
      const fetchResource = new Resource('sales/fetch-debts');
      fetchResource.list(param).then(response => {
        app.debts = response.debts.data;
        app.debts.forEach((element, index) => {
          element['index'] = (page - 1) * limit + index + 1;
        });
        app.total = response.debts.total;
        app.currency = response.currency;
        app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
        app.load_table = false;
      });
    },
  },
};
</script>

<style lang="scss">
/*! rtl:begin:ignore */
#dashboard-analytics {
  .greet-user{
    position: relative;

    .decore-left{
      position: absolute;
      left:0;
      top: 0;
    }
    .decore-right{
      position: absolute;
      right:0;
      top: 0;
    }
  }

  @media(max-width: 576px) {
    .decore-left, .decore-right{
      width: 140px;
    }
  }
}
/*! rtl:end:ignore */
</style>
