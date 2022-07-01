<template>
  <vx-card>
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
      <filter-options @submitQuery="fetchReports" />
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
  </vx-card>
</template>
<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
import checkPermission from '@/utils/permission'; // Permission checking
import VisitDetails from './partials/VisitDetails';
import FilterOptions from '@/views/apps/reports/FilterOptions';
export default {
  components: { Pagination, VisitDetails, FilterOptions },
  //   props: {
  //     reps: {
  //       type: Array,
  //       default: () => [],
  //     },
  //   },
  data() {
    return {
      reps: [],
      customers: [],
      visits: [],
      visits_columns: [
        'customer.business_name',
        'visit_type',
        'proximity',
        'next_appointment_date',
        'contact.name',
        'purpose',
        // 'market_feedback',
        'visited_by.name',
        'visit_duration',
        'created_at',
        'action',
      ],

      visits_options: {
        headings: {
          next_appointment_date: 'Next Appointment',
          proximity: 'Proximity (M)',
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
    // this.fetchSalesReps();
  },
  methods: {
    moment,
    checkPermission,
    fetchReports(param) {
      const app = this;
      app.form = param;
      app.load = true;
      const { limit, page } = app.form;
      app.visits_options.perPage = limit;
      const visitsResource = new Resource('visits/fetch-general-visits');
      // const param = app.form;
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
        const multiHeader = [['Visits ' + this.sub_title, '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'VISIT TYPE',
          'PROXIMITY (M)',
          'FOLLOW-UP SCHEDULE',
          'CONTACTED PERSONNEL',
          'PURPOSE',
          'RELATING OFFICER',
          'VISIT DURATION',
          'CREATED AT',
        ];
        const filterVal = [
          'customer.business_name',
          'visit_type',
          'proximity',
          'next_appointment_date',
          'contact.name',
          'purpose',
          'visited_by.name',
          'visit_duration',
          'created_at',
        ];
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
            return (v['customer']) ? v['customer']['business_name'] : '';
          }
          if (j === 'contact.name') {
            return (v['contact']) ? v['contact']['name'] : '';
          }
          if (j === 'visited_by.name') {
            return (v['visited_by']) ? v['visited_by']['name'] : '';
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
