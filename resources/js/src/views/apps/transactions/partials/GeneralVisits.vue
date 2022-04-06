<template>

  <div v-loading="load">
    <div slot="header" class="clearfix">
      <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
      <strong class="font-medium text-lg">General Customer Visits Report {{ sub_title }}</strong>
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
        <el-select v-model="form.customer_id" style="width: 100%" @input="fetchGeneralVisitReports">
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
          <el-button id="pick_date5" slot="reference" type="primary">
            <i class="el-icon-date" /> Pick Date Range
          </el-button>
        </el-popover>
      </el-col>
    </el-row>
    <el-row :gutter="10">
      <v-client-table v-model="visit_details" :columns="visit_details_columns" :options="visit_details_options">
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
        @pagination="fetchGeneralVisitReports"
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
      visit_details: [],
      visit_details_columns: [
        'visit.customer.business_name',
        'contact.name',
        'visit_type',
        'purpose',
        'description',
        'visit.visited_by.name',
        'created_at',
      ],

      visit_details_options: {
        headings: {
          'visit.customer.business_name': 'Customer',
          'contact.name': 'Personnel Contacted',
          'visit.visited_by.name': 'Relating Officer',
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
        filterable: ['visit.customer.business_name'],
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
    this.fetchGeneralVisitReports();
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
      document.getElementById('pick_date5').click();
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
      app.fetchGeneralVisitReports();
    },
    fetchGeneralVisitReports() {
      const app = this;
      const { limit, page } = app.form;
      app.visit_details_options.perPage = limit;
      const visit_detailsResource = new Resource('visits/fetch-general-visits');
      const param = app.form;
      visit_detailsResource.list(param)
        .then(response => {
          app.visit_details = response.visit_details.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.visit_details.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.visit_details.total;
        });
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Collections ' + this.sub_title, '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'PERSONNEL CONTACTED',
          'VISIT TYPE',
          'PURPOSE',
          'DESCRIPTION',
          'RELATING OFFICER',
          'CREATED AT',
        ];
        const filterVal = this.visit_details_columns;
        const list = this.visit_details;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'General Customer Visit Report',
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
          if (j === 'visit.customer.business_name') {
            return v['visit']['customer']['business_name'];
          }
          if (j === 'visit.visited_by.name') {
            return v['visit']['visited_by']['name'];
          }
          if (j === 'contact.name') {
            return v['contact']['name'];
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
