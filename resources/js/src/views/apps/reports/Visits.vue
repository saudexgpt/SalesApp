<template>
  <vx-card>
    <div v-if="page === 'list'">
      <div slot="header" class="clearfix">
        <feather-icon svg-classes="w-6 h-6" icon="HomeIcon" class="mr-2" />
        <strong class="font-medium text-lg">Sales Rep Visits Report {{ sub_title }}</strong>
      </div>
      <filter-options @submitQuery="fetchReports" />
      <el-row v-if="show_chart" :gutter="20">
        <el-col :md="12">
          <vue-apex-charts
            :options="chartOptions1"
            :series="series1"
            width="99%"
            type="pie"
          />
        </el-col>
        <el-col :md="12">
          <vue-apex-charts
            :options="chartOptions2"
            :series="series2"
            width="99%"
            type="pie"
          />
        </el-col>
        <!-- <el-col :md="12">
          <h4>All Visits made</h4>
          <aside>
            <p>Company's Customers: {{ visited_company_customers }}</p>
            <p>Rep's Customers: {{ visited_rep_customers }}</p>
          </aside>
          <h4>Visits {{ sub_title }}</h4>
          <aside>
            <p>Company's Customers: {{ company_customers_visits }}</p>
            <p>Rep's Customers: {{ reps_customers_visits }}</p>
          </aside>
        </el-col> -->
        <!-- <el-col :md="12">

          <vue-apex-charts
            v-if="show_chart"
            :options="chartOptions3"
            :series="series3"
            width="99%"
            type="pie"
          />
        </el-col> -->
      </el-row>
      <el-row v-loading="load" :gutter="10">

        <span style="float: right">
          <el-button
            v-if="visits.length > 0"
            :loading="downloadLoading"
            round
            style="margin:0 0 20px 20px;"
            type="success"
            icon="el-icon-download"
            size="small"
            @click="handleDownload"
          >Export Excel</el-button>
        </span>
        <br>
        <v-client-table v-model="visits" :columns="visits_columns" :options="visits_options">
          <div
            slot="customer.business_name"
            slot-scope="props">
            {{ props.row.customer.business_name }} <br>
            <small>{{ props.row.customer.address }}</small>
          </div>
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
          @pagination="fetchReports(form)"
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
import VueApexCharts from 'vue-apexcharts';
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
import checkPermission from '@/utils/permission'; // Permission checking
import VisitDetails from './partials/VisitDetails';
import FilterOptions from '@/views/apps/reports/FilterOptions';
export default {
  components: { Pagination, VisitDetails, FilterOptions, VueApexCharts },
  //   props: {
  //     reps: {
  //       type: Array,
  //       default: () => [],
  //     },
  //   },
  data() {
    return {
      show_chart: false,
      series1: [0, 0],
      series2: [0, 0],
      chartOptions1: {
        chart: {
          width: 350,
          type: 'pie',
        },
        plotOptions: {
          pie: {
            startAngle: -90,
            endAngle: 270,
          },
        },
        labels: ['Company\'s Visited Customers', 'Company\'s Unvisited Customers'],
        colors: ['#1fa30e', '#D12929'],
        dataLabels: {
          enabled: true,
        },
        fill: {
          type: 'gradient',
        },
        legend: {
          formatter: function(val, opts) {
            return val + ': ' + opts.w.globals.series[opts.seriesIndex];
          },
          // position: 'bottom',
          labels: {
            colors: ['#000000'],
          },
        },
        title: {
          text: 'Company\'s Customers Stat',
          align: 'center',
          style: {
            color: '#000000',
          },
        },
        responsive: [{
          breakpoint: 480,
          options: {
            chart: {
              width: 350,
            },
            legend: {
              position: 'bottom',
            },
          },
        }],
      },
      chartOptions2: {
        chart: {
          width: 350,
          type: 'pie',
        },
        plotOptions: {
          pie: {
            startAngle: -90,
            endAngle: 270,
          },
        },
        labels: ['Rep\'s Visited Customers', 'Rep\'s Unvisited Customers'],
        colors: ['#1fa30e', '#D12929'],
        dataLabels: {
          enabled: true,
        },
        fill: {
          type: 'gradient',
        },
        legend: {
          formatter: function(val, opts) {
            return val + ': ' + opts.w.globals.series[opts.seriesIndex];
          },
          // position: 'bottom',
          labels: {
            colors: ['#000000'],
          },
        },
        title: {
          text: 'Rep\'s Customers Stat',
          align: 'center',
          style: {
            color: '#000000',
          },
        },
        responsive: [{
          breakpoint: 480,
          options: {
            chart: {
              width: 350,
            },
            legend: {
              position: 'bottom',
            },
          },
        }],
      },
      visited_company_customers: 0,
      visited_rep_customers: 0,
      company_customers_visits: 0,
      reps_customers_visits: 0,
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
        'customer.registrar.name',
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
          'customer.registrar.name': 'Registered By',
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
    showDetails(visit) {
      const app = this;
      app.selected_visit = visit;
      app.page = 'details';
    },
    fetchReports(param) {
      const app = this;
      app.fetchVisitStat(param);
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
    fetchVisitStat(param) {
      const app = this;
      app.show_chart = false;
      const visitsResource = new Resource('visits/customer-visit-stat');
      // const param = app.form;
      app.series1[0] = app.series1[1] = 0;
      visitsResource.list(param)
        .then(response => {
          app.series1[0] = response.company_customers_visits;
          app.series1[1] = response.notvisited_company_customers;

          app.series2[0] = response.reps_customers_visits;
          app.series2[1] = response.notvisited_rep_customers;

          //   app.company_customers_visits = response.company_customers_visits;
          //   app.reps_customers_visits = response.reps_customers_visits;

          //   app.visited_company_customers = response.visited_company_customers;
          //   app.visited_rep_customers = response.visited_rep_customers;
          app.show_chart = true;
        });
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Visits ' + this.sub_title, '', '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'VISIT TYPE',
          'PROXIMITY (M)',
          'FOLLOW-UP SCHEDULE',
          'CONTACTED PERSONNEL',
          'PURPOSE',
          'REGISTERED BY',
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
          'customer.registrar.name',
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
          if (j === 'customer.registrar.name') {
            return (v['customer']) ? v['customer']['registrar']['name'] : '';
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
