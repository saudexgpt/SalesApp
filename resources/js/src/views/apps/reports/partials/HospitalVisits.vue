<template>

  <div v-loading="load">
    <div slot="header" class="clearfix">
      <feather-icon svg-classes="w-6 h-6" icon="HomeIcon" class="mr-2" />
      <strong class="font-medium text-lg">Hospital Visits Report {{ sub_title }}</strong>
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
        <el-select v-model="form.customer_id" style="width: 100%" @input="fetchReports">
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
          <el-button id="pick_date6" slot="reference" type="primary">
            <i class="el-icon-date" /> Pick Date Range
          </el-button>
        </el-popover>
      </el-col>
    </el-row>
    <el-row :gutter="10">
      <v-client-table v-model="hospital_reports" :columns="hospital_reports_columns" :options="hospital_reports_options">
        <div
          slot="follow_up_schedule"
          slot-scope="props"
        >{{ moment(props.row.follow_up_schedule).format('lll') }}</div>
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
        @pagination="fetchReports"
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
      hospital_reports: [],
      hospital_reports_columns: [
        'customer.business_name',
        'marketed_products',
        'follow_up_schedule',
        'personnel_contacted',
        'market_feedback',
        'daily_report.reporter.name',
        'created_at',
      ],

      hospital_reports_options: {
        headings: {
          'customer.business_name': 'Customer',
          'daily_report.reporter.name': 'Relating Officer',
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
    this.fetchReports();
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
      document.getElementById('pick_date6').click();
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
      app.fetchReports();
    },
    fetchReports() {
      const app = this;
      const { limit, page } = app.form;
      app.hospital_reports_options.perPage = limit;
      const hospital_reportsResource = new Resource('visits/fetch-hospital-visits');
      const param = app.form;
      hospital_reportsResource.list(param)
        .then(response => {
          app.hospital_reports = response.hospital_reports.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.hospital_reports.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.hospital_reports.total;
        });
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Collections ' + this.sub_title, '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'MARKETED PRODUCTS',
          'FOLLOW-UP SCHEDULE',
          'PERSONNEL CONTACTED',
          'FEEDBACK',
          'RELATING OFFICER',
          'CREATED AT',
        ];
        const filterVal = this.hospital_reports_columns;
        const list = this.hospital_reports;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Hospital Visit Report',
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v =>
        filterVal.map(j => {
          if (j === 'created_at') {
            return (v[j]) ? moment(v[j]).format('lll') : '';
          }
          if (j === 'customer.business_name') {
            return v['customer']['business_name'];
          }
          if (j === 'daily_report.reporter.name') {
            return v['daily_report']['reporter']['name'];
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
