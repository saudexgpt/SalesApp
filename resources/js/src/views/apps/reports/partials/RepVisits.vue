<template>
  <div>
    <div v-if="page === 'list'">
      <div slot="header" class="clearfix">
        <feather-icon svg-classes="w-6 h-6" icon="HomeIcon" class="mr-2" />
        <strong class="font-medium text-lg">Sales Rep Visits Report {{ sub_title }}</strong>
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
        <el-col :lg="8" :md="8" :sm="8" :xs="24">
          <label for="">Select Rep</label>
          <el-select v-model="form.rep_id" filterable style="width: 100%" @input="fetchCustomers($event)">
            <el-option
              v-if="reps.length > 0"
              label="All"
              value="all" />
            <el-option
              v-for="(rep, index) in reps"
              :key="index"
              :label="rep.name"
              :value="rep.id"

            />
          </el-select>
        </el-col>
        <el-col v-loading="load_customer" :lg="8" :md="8" :sm="8" :xs="24">
          <label for="">Select Customer</label>
          <el-select v-model="form.customer_id" filterable style="width: 100%" @input="fetchReports()">
            <el-option
              v-if="customers.length > 0"
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
        <el-col :lg="8" :md="8" :sm="8" :xs="24">
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
      <el-row v-loading="load" :gutter="10">
        <br>
        <v-client-table v-model="visits" :columns="visits_columns" :options="visits_options">
          <div
            slot="next_appointment_date"
            slot-scope="props"
          >{{ (props.row.next_appointment_date) ? moment(props.row.next_appointment_date).format('lll') : '' }}
          </div>
          <div
            slot="created_at"
            slot-scope="props"
          >{{ moment(props.row.created_at).format('lll') }}</div>
          <div
            slot="action"
            slot-scope="props"
          >
            <el-tooltip
              class="item"
              effect="dark"
              content="Visit Details"
              placement="top-start"
            >
              <el-button
                round
                type="primary"
                size="small"
                icon="el-icon-tickets"
                @click="showDetails(props.row)"
              />
            </el-tooltip>
          </div>
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
    <div v-if="page === 'details'">
      <div slot="header" class="clearfix">
        <feather-icon svg-classes="w-6 h-6" icon="tickets" class="mr-2" />
        <strong class="font-medium text-lg">Visit Details for {{ selected_visit.customer.business_name }}</strong>
        <span style="float: right">
          <el-button
            round
            style="margin:0 0 20px 20px;"
            type="danger"
            icon="el-icon-back"
            size="small"
            @click="page = 'list'"
          >Back</el-button>
        </span>
      </div>
      <visit-details :visit="selected_visit" />
    </div>
  </div>
</template>
<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
import checkPermission from '@/utils/permission'; // Permission checking
import VisitDetails from './VisitDetails';
export default {
  components: { Pagination, VisitDetails },
  props: {
    reps: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      customers: [],
      visits: [],
      visits_columns: [
        'customer.business_name',
        'visit_type',
        'next_appointment_date',
        'contact.name',
        // 'market_feedback',
        'visited_by.name',
        'visit_duration',
        'created_at',
        'action',
      ],

      visits_options: {
        headings: {
          next_appointment_date: 'Next Appointment',
          'customer.business_name': 'Customer',
          'visited_by.name': 'Visited By',
          'contact.name': 'Personnel Contacted',
          created_at: 'Date',
          action: '',
        },
        pagination: {
          dropdown: true,
          chunk: 100,
        },
        perPage: 100,
        filterByColumn: false,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['created_at'],
        filterable: ['customer.business_name', 'next_appointment_date', 'created_at'],
      },
      load: false,
      load_customer: false,
      total: 0,
      currency: '',
      form: {
        from: '',
        to: '',
        panel: '',
        status: 'pending',
        page: 1,
        limit: 10,
        rep_id: 'all',
      },
      sub_title: '',
      submitTitle: 'Fetch Report',
      panel: 'month',
      future: false,
      panels: ['range', 'week', 'month', 'quarter', 'year'],
      show_calendar: false,
      downloadLoading: false,
      selected_visit: '',
      page: 'list',
    };
  },
  created() {
    // this.fetchReports();
  },
  methods: {
    moment,
    checkPermission,
    format(date) {
      var month = date.toLocaleString('en-US', { month: 'short' });
      return month + ' ' + date.getDate() + ', ' + date.getFullYear();
    },
    showDetails(visit) {
      const app = this;
      app.selected_visit = visit;
      app.page = 'details';
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
    fetchCustomers(rep_id) {
      const app = this;
      app.load_customer = true;
      const customerResource = new Resource('customers/rep-customers');
      const param = { rep_id };
      customerResource.list(param)
        .then(response => {
          app.customers = response.customers;
          app.load_customer = false;
        });
    },
    fetchReports() {
      const app = this;
      app.load = true;
      const { limit, page } = app.form;
      app.visits_options.perPage = limit;
      const visitsResource = new Resource('visits/fetch-general-visits');
      const param = app.form;
      visitsResource.list(param)
        .then(response => {
          app.visits = response.visits.data;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.visits.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.visits.total;
          app.load = false;
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
        const filterVal = this.visits_columns;
        const list = this.visits;
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
