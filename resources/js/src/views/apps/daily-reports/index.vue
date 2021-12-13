<template>
  <el-card class="box-card">
    <div v-loading="load_table" v-if="page==='list'" class="app-container">
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <div class="flex staffs-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
            <span class="font-medium text-lg">Daily Report {{ sub_title }}</span>
          </div>
          <vs-divider />
        </div>
        <div class="vx-col lg:w-1/4 w-full">
          <div class="flex staffs-end px-3">
            <span class="pull-right">
              <div v-if="!checkRole(['sales_rep'])" class="block">
                <span class="demonstration">Select Rep</span>
                <el-select v-model="form.user_id" placeholder="Select Reps" @input="fetchDailyReports()">
                  <el-option
                    v-for="(rep, index) in sales_reps"
                    :key="index"
                    :label="rep.name"
                    :value="rep.id"
                  />
                </el-select>
              </div>
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
      </div>
      <v-client-table
        v-model="daily_reports"
        :columns="columns"
        :options="options"
      >
        <template slot="work_with_manager_check" slot-scope="{row}">
          <span>{{ (row.work_with_manager_check === '1') ? 'Yes' : 'No' }}</span>
        </template>
        <template slot="date" slot-scope="{row}">
          <span>{{ moment(row.date).format('ll') }}</span>
        </template>
        <template slot="created_at" slot-scope="{row}">
          <span>{{ moment(row.created_at).format('ll') }}</span>
        </template>
        <template slot="action" slot-scope="{row}">
          <el-tooltip
            class="item"
            effect="dark"
            content="View Report Details"
            placement="top-start"
          >
            <router-link
              :to="'/daily-report/details/' + row.id + '/' + row.report_by"
            >
              <el-button
                circle
                type="success"
                size="small"
                icon="el-icon-view"
              />
            </router-link>
          </el-tooltip>
        </template>
      </v-client-table>
    </div>
  </el-card>
</template>

<script>
import moment from 'moment';
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
import checkRole from '@/utils/role'; // Permission checking
const dailyReportResource = new Resource('daily-report/my-reports');
export default {
  name: 'Customers',
  components: { Pagination },
  directives: { permission },
  data() {
    return {
      daily_reports: [],
      sub_title: '',
      submitTitle: 'Fetch Report',
      panel: 'month',
      future: false,
      panels: ['range', 'week', 'month', 'quarter', 'year'],
      show_calendar: false,
      form: {
        from: '',
        to: '',
        panel: '',
        status: 'pending',
        page: 1,
        limit: 10,
        keyword: '',
        user_id: '',
      },
      sales_reps: [],
      columns: [
        'action',
        'reporter.name',
        'work_with_manager_check',
        'time_duration_with_manager',
        'relationship_with_manager',
        'date',
        'created_at',
      ],

      options: {
        headings: {
          'reporter.name': 'Staff',
          work_with_manager_check: 'Work with Manager',
          time_duration_with_manager: 'Time with Manager (HRS)',
          relationship_with_manager: 'Manager Relationship',
          date: 'Report Date',
          created_at: 'Entry Date',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        // filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['reporter.name', 'date', 'created_at'],
        filterable: ['reporter.name', 'date', 'created_at'],
      },
      page: 'list',
    };
  },
  created() {
    this.fetchSalesRep();
    this.fetchDailyReports();
  },
  methods: {
    moment,
    checkPermission,
    checkRole,
    fetchSalesRep() {
      const app = this;
      const salesRepResource = new Resource('users/fetch-sales-reps');
      salesRepResource
        .list()
        .then((response) => {
          app.sales_reps = response.sales_reps;
        })
        .catch((error) => {
          console.log(error);
          this.load_table = false;
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
      app.fetchDailyReports();
    },
    fetchDailyReports() {
      const app = this;
      this.load_table = true;
      const param = app.form;
      dailyReportResource
        .list(param)
        .then((response) => {
          this.daily_reports = response.daily_reports;
          this.load_table = false;
        })
        .catch((error) => {
          console.log(error);
          this.load_table = false;
        });
    },
    showDetails(row) {

    },
    handleDownload(){
      const app = this;
      app.export(app.list);
    //   const param = { staff_id: app.selected_staff.id };
    //   this.downloading = true;
    //   staffResource.list(param)
    //     .then(response => {
    //       this.export(response.data);

    //       this.downloading = false;
    //     });
    },
    export(export_data) {
      import('@/vendor/Export2Excel').then((excel) => {
        const tHeader = [
          'STAFF',
          'TOTAL STOCKED',
          'TOTAL SOLD',
          'TOTAL BALANCE',
        ];
        const filterVal = [
          'staff.name',
          'total_stocked',
          'total_sold',
          'total_balance',
        ];
        const data = this.formatJson(filterVal, export_data);
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'inventory-by-product',
        });
        this.downloading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'staff.name') {
            return v['staff']['name'];
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
<style>
.vs-con-input {
    margin-top: 20px !important ;
}
.dialog-footer {
    background: #f0f0f0;
    padding: 10px;
    margin-top: 20px !important ;
    position: relative;
}
</style>
