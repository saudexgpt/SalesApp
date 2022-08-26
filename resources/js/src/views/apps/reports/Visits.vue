<template>
  <vx-card>
    <div v-if="page === 'list'">
      <div slot="header" class="clearfix">
        <feather-icon svg-classes="w-6 h-6" icon="HomeIcon" class="mr-2" />
        <strong class="font-medium text-lg">Sales Rep Visits Report {{ sub_title }}</strong>
      </div>
      <el-row :gutter="20">
        <el-col :span="24">
          <filter-options @submitQuery="fetchReports" />
        </el-col>
      </el-row>
      <br><br>
      <el-row :gutter="10">
        <el-col :md="6">
          <el-card shadow="always">
            <div slot="header" class="clearfix">
              <span>Total Visits: {{ total }}</span>
            </div>
            <h4>Scheduled: {{ scheduled_visits.length }}</h4>
            <h4>Unscheduled: {{ unscheduled_visits.length }}</h4>
          </el-card>
        </el-col>
        <el-col :md="6">
          <el-card shadow="always">
            <div slot="header" class="clearfix">
              <span>Total Schedules: {{ parseInt(scheduled_visits.length + unvisited_schedule.length) }}</span>
            </div>
            <h4>Visited: {{ scheduled_visits.length }}</h4>
            <h4>Unvisited: {{ unvisited_schedule.length }}</h4>
          </el-card>
        </el-col>
        <el-col :md="6">
          <el-card shadow="always">
            <div slot="header" class="clearfix">
              <span>Company's Customers: {{ company_customers }}</span>
            </div>
            <h4>Visited: {{ company_customers_visits.length }}</h4>
            <h4>Unvisited: {{ notvisited_company_customers.length }}</h4>
          </el-card>
        </el-col>
        <el-col :md="6">
          <el-card shadow="always">
            <div slot="header" class="clearfix">
              <span>Rep's Customers: {{ reps_customers }}</span>
            </div>
            <h4>Visited: {{ reps_customers_visits.length }}</h4>
            <h4>Unvisited: {{ notvisited_rep_customers.length }}</h4>
          </el-card>
        </el-col>
      </el-row>
      <hr>
      <el-row v-loading="load" :gutter="10">
        <vs-tabs position="left" color="danger">
          <vs-tab label="All Visits">
            <div class="clearfix">
              <h3>All Visits</h3>
            </div>
            <span style="float: right">
              <el-button
                v-if="visits.length > 0"
                :loading="downloadLoading"
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-download"
                size="small"
                @click="handleDownload(visits, 'All Visits Report')"
              >Export Excel</el-button>
            </span>
            <v-client-table v-model="visits" :columns="visits_columns" :options="visits_options">
              <div
                slot="customer.business_name"
                slot-scope="props">
                {{ (props.row.customer) ? props.row.customer.business_name : '' }} <br>
                <small>{{ (props.row.customer) ? props.row.customer.address : '' }}</small>
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

            <el-row :gutter="20">
              <pagination
                v-show="total > 0"
                :total="total"
                :page.sync="form.page"
                :limit.sync="form.limit"
                @pagination="fetchReports(form)"
              />
            </el-row>
          </vs-tab>
          <vs-tab label="Scheduled Visits">
            <div class="clearfix">
              <h3>Scheduled Visits</h3>
            </div>
            <span style="float: right">
              <el-button
                v-if="scheduled_visits.length > 0"
                :loading="downloadLoading"
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-download"
                size="small"
                @click="handleDownload(scheduled_visits, 'Scheduled Customers Visits Report')"
              >Export Excel</el-button>
            </span>
            <v-client-table v-model="scheduled_visits" :columns="visits_columns" :options="visits_options">
              <div
                slot="customer.business_name"
                slot-scope="props">
                {{ (props.row.customer) ? props.row.customer.business_name : '' }} <br>
                <small>{{ (props.row.customer) ? props.row.customer.address : '' }}</small>
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
          </vs-tab>
          <vs-tab label="Unscheduled Visits">
            <div class="clearfix">
              <h3>Unscheduled Visits</h3>
            </div>
            <span style="float: right">
              <el-button
                v-if="unscheduled_visits.length > 0"
                :loading="downloadLoading"
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-download"
                size="small"
                @click="handleDownload(unscheduled_visits, 'Unscheduled Customers Visits Report')"
              >Export Excel</el-button>
            </span>
            <v-client-table v-model="unscheduled_visits" :columns="visits_columns" :options="visits_options">
              <div
                slot="customer.business_name"
                slot-scope="props">
                {{ (props.row.customer) ? props.row.customer.business_name : '' }} <br>
                <small>{{ (props.row.customer) ? props.row.customer.address : '' }}</small>
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
          </vs-tab>
          <vs-tab label="Unvisited Schedules">
            <div class="clearfix">
              <h3>Unvisited Schedules</h3>
            </div>
            <span style="float: right">
              <el-button
                v-if="visits.length > 0"
                :loading="downloadLoading"
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-download"
                size="small"
                @click="handleDownload3(unvisited_schedule, 'Unvisited Schedule Report')"
              >Export Excel</el-button>
            </span>
            <v-client-table v-model="unvisited_schedule" :columns="unvisited_schedule_columns" :options="unvisited_schedule_options">
              <div
                slot="customer.business_name"
                slot-scope="props">
                {{ (props.row.customer) ? props.row.customer.business_name : '' }} <br>
                <small>{{ (props.row.customer) ? props.row.customer.longitude + ',' + props.row.customer.latitude : '' }}</small>
              </div>
            </v-client-table>
          </vs-tab>
          <vs-tab label="Company's Visited">
            <div class="clearfix">
              <h3>Company's Visited</h3>
            </div>
            <span style="float: right">
              <el-button
                v-if="company_customers_visits.length > 0"
                :loading="downloadLoading"
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-download"
                size="small"
                @click="handleDownload(company_customers_visits, 'Company\'s Visited Customers Report')"
              >Export Excel</el-button>
            </span>
            <v-client-table v-model="company_customers_visits" :columns="visits_columns" :options="visits_options">
              <div
                slot="customer.business_name"
                slot-scope="props">
                {{ (props.row.customer) ? props.row.customer.business_name : '' }} <br>
                <small>{{ (props.row.customer) ? props.row.customer.address : '' }}</small>
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
          </vs-tab>
          <vs-tab label="Company's Unvisited">
            <div class="clearfix">
              <h3>Company's Unvisited</h3>
            </div>
            <span style="float: right">
              <el-button
                v-if="notvisited_company_customers.length > 0"
                :loading="downloadLoading"
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-download"
                size="small"
                @click="handleDownload2(notvisited_company_customers, 'Company\'s Unvisited Customers Report')"
              >Export Excel</el-button>
            </span>
            <v-client-table v-model="notvisited_company_customers" :columns="unvisited_cust_columns" :options="unvisited_cust_options">
              <div
                slot="customer.business_name"
                slot-scope="props">
                {{ (props.row.customer) ? props.row.customer.business_name : '' }} <br>
                <small>{{ (props.row.customer) ? props.row.customer.address : '' }}</small>
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
          </vs-tab>
          <vs-tab label="Rep's Visited">
            <div class="clearfix">
              <h3>Rep's Visited</h3>
            </div>
            <span style="float: right">
              <el-button
                v-if="reps_customers_visits.length > 0"
                :loading="downloadLoading"
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-download"
                size="small"
                @click="handleDownload(reps_customers_visits, 'Rep\'s Visited Customers Report')"
              >Export Excel</el-button>
            </span>
            <v-client-table v-model="reps_customers_visits" :columns="visits_columns" :options="visits_options">
              <div
                slot="customer.business_name"
                slot-scope="props">
                {{ (props.row.customer) ? props.row.customer.business_name : '' }} <br>
                <small>{{ (props.row.customer) ? props.row.customer.address : '' }}</small>
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
          </vs-tab>
          <vs-tab label="Rep's Unvisited">
            <div class="clearfix">
              <h3>Rep's Unvisited</h3>
            </div>
            <span style="float: right">
              <el-button
                v-if="notvisited_rep_customers.length > 0"
                :loading="downloadLoading"
                round
                style="margin:0 0 20px 20px;"
                type="success"
                icon="el-icon-download"
                size="small"
                @click="handleDownload2(notvisited_rep_customers, 'Rep\'s Unvisited Customers Report')"
              >Export Excel</el-button>
            </span>
            <v-client-table v-model="notvisited_rep_customers" :columns="unvisited_cust_columns" :options="unvisited_cust_options">
              <div
                slot="customer.business_name"
                slot-scope="props">
                {{ (props.row.customer) ? props.row.customer.business_name : '' }} <br>
                <small>{{ (props.row.customer) ? props.row.customer.address : '' }}</small>
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
          </vs-tab>
        </vs-tabs>
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
      // series2: [0, 0],
      //   chartOptions1: {
      //     chart: {
      //       width: 350,
      //       type: 'pie',
      //     },
      //     plotOptions: {
      //       pie: {
      //         startAngle: -90,
      //         endAngle: 270,
      //       },
      //     },
      //     labels: ['Scheduled Visits', 'Unscheduled Visits'],
      //     colors: ['#1fa30e', '#D12929'],
      //     dataLabels: {
      //       enabled: true,
      //     },
      //     fill: {
      //       type: 'gradient',
      //     },
      //     legend: {
      //       formatter: function(val, opts) {
      //         return val + ': ' + opts.w.globals.series[opts.seriesIndex];
      //       },
      //       // position: 'bottom',
      //       labels: {
      //         colors: ['#000000'],
      //       },
      //     },
      //     title: {
      //       text: 'Visit Schedule Chart',
      //       align: 'center',
      //       style: {
      //         color: '#000000',
      //       },
      //     },
      //     responsive: [{
      //       breakpoint: 480,
      //       options: {
      //         chart: {
      //           width: 350,
      //         },
      //         legend: {
      //           position: 'bottom',
      //         },
      //       },
      //     }],
      //   },
      //   chartOptions2: {
      //     chart: {
      //       width: 350,
      //       type: 'pie',
      //     },
      //     plotOptions: {
      //       pie: {
      //         startAngle: -90,
      //         endAngle: 270,
      //       },
      //     },
      //     labels: ['Rep\'s Visited Customers', 'Rep\'s Unvisited Customers'],
      //     colors: ['#1fa30e', '#D12929'],
      //     dataLabels: {
      //       enabled: true,
      //     },
      //     fill: {
      //       type: 'gradient',
      //     },
      //     legend: {
      //       formatter: function(val, opts) {
      //         return val + ': ' + opts.w.globals.series[opts.seriesIndex];
      //       },
      //       // position: 'bottom',
      //       labels: {
      //         colors: ['#000000'],
      //       },
      //     },
      //     title: {
      //       text: 'Rep\'s Customers Stat',
      //       align: 'center',
      //       style: {
      //         color: '#000000',
      //       },
      //     },
      //     responsive: [{
      //       breakpoint: 480,
      //       options: {
      //         chart: {
      //           width: 350,
      //         },
      //         legend: {
      //           position: 'bottom',
      //         },
      //       },
      //     }],
      //   },
      reps: [],
      customers: [],
      scheduled_visits: [],
      company_customers: 0,
      reps_customers: 0,
      unscheduled_visits: [],
      unvisited_schedule: [],
      company_customers_visits: [],
      reps_customers_visits: [],
      notvisited_company_customers: [],
      notvisited_rep_customers: [],
      visits: [],
      visits_columns: [
        'action',
        'customer.business_name',
        'visit_type',
        'proximity',
        // 'next_appointment_date',
        'contact.name',
        'purpose',
        // 'market_feedback',
        'customer.registrar.name',
        'visited_by.name',
        'visit_duration',
        'created_at',
      ],
      unvisited_cust_columns: [
        'business_name',
        'address',
        'latitude',
        'longitude',
        'assigned_officer.name',
      ],
      unvisited_schedule_columns: [
        'customer.business_name',
        'customer.address',
        'day',
        'schedule_date',
        'rep.name',
        'scheduled_by.name',
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
        rowAttributesCallback(row) {
          if (row.registered_by === 1) {
            return { style: 'background: #00b95df8; color: #000000' };
          }
          // return { style: 'background: #36c15ecf; color: #000000' };
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
      unvisited_cust_options: {
        headings: {
          'assigned_officer.name': 'REP',
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
        sortable: ['business_name'],
        filterable: ['business_name', 'address'],
      },
      unvisited_schedule_options: {
        headings: {
          'customer.business_name': 'Customer',
          'customer.address': 'Address',
          'scheduled_by.name': 'Scheduled By',
          'rep.name': 'Rep',
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
        sortable: ['customer.business_name'],
        filterable: ['customer.business_name', 'day'],
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
          app.series1[0] = response.scheduled_visits.length;
          app.series1[1] = response.unscheduled_visits.length;

          //   app.series2[0] = response.reps_customers_visits.length;
          //   app.series2[1] = response.notvisited_rep_customers.length;

          app.scheduled_visits = response.scheduled_visits;
          app.unscheduled_visits = response.unscheduled_visits;
          app.company_customers = response.company_customers;
          app.reps_customers = response.reps_customers;
          app.unvisited_schedule = response.unvisited_schedule;

          app.company_customers_visits = response.company_customers_visits;
          app.reps_customers_visits = response.reps_customers_visits;
          app.notvisited_company_customers = response.notvisited_company_customers;
          app.notvisited_rep_customers = response.notvisited_rep_customers;
          app.show_chart = true;
        });
    },
    handleDownload(list, title) {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [[title + ' ' + this.sub_title, '', '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'VISIT TYPE',
          'PROXIMITY (M)',
          // 'FOLLOW-UP SCHEDULE',
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
          // 'next_appointment_date',
          'contact.name',
          'purpose',
          'customer.registrar.name',
          'visited_by.name',
          'visit_duration',
          'created_at',
        ];
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: title,
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    handleDownload2(list, title) {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [[title + ' ' + this.sub_title, '', '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'ADDRESS',
          'LATITUDE',
          'LONGITUDE',
          'REP',
        ];
        const filterVal = [
          'business_name',
          'address',
          'latitude',
          'longitude',
          'assigned_officer.name',
        ];
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: title,
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    handleDownload3(list, title) {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [[title + ' ' + this.sub_title, '', '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'ADDRESS',
          'DAY',
          'SCHEDULE DATE',
          'REP',
          'SCHEDULED BY',
        ];
        const filterVal = [
          'customer.business_name',
          'customer.address',
          'day',
          'schedule_date',
          'rep.name',
          'scheduled_by.name',
        ];
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: title,
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
          if (j === 'customer.address') {
            return (v['customer']) ? v['customer']['address'] : '';
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
          if (j === 'scheduled_by.name') {
            return (v['scheduled_by']) ? v['scheduled_by']['name'] : '';
          }
          if (j === 'rep.name') {
            return (v['rep']) ? v['rep']['name'] : '';
          }
          if (j === 'assigned_officer.name') {
            return (v['assigned_officer']) ? v['assigned_officer']['name'] : '';
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
