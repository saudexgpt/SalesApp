<template>
  <vx-card title="Transactions">

    <div class="vx-col lg:w-1/4 w-full">
      <div class="flex staffs-end px-3">
        <span class="pull-right">
          <!-- <div class="block">
              <span class="demonstration">Pick Month</span>
              <el-date-picker
                v-model="form.month"
                type="month"
                placeholder="Pick a month"/>
            </div> -->
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
    <div class="flex">
      <span class="flex items-center"><div class="h-3 w-3 rounded-full mr-1 bg-primary"/><span>Sales</span></span>
      <span class="flex items-center ml-4"><div class="h-3 w-3 rounded-full mr-1 bg-danger"/><span>Debts</span></span>
    </div>
    <vue-apex-charts :options="clientRetentionBar.chartOptions" :series="clientRetentionBar.series" type="bar" height="277" />
  </vx-card>
</template>
<script>

import VueApexCharts from 'vue-apexcharts';
import Resource from '@/api/resource';
export default {
  components: { VueApexCharts },
  data() {
    return {
      clientRetentionBar: {
        series: [{
          name: 'Sales',
          data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        }, {
          name: 'Debts',
          data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        }],
        chartOptions: {
          stroke: {
            lineCap: 'round',
          },
          grid: {
            borderColor: '#ebebeb',
            padding: {
              left: 0,
              right: 0,
            },
          },
          legend: {
            show: false,
          },
          dataLabels: {
            enabled: false,
          },
          chart: {
            stacked: true,
            type: 'bar',
            toolbar: { show: false },
          },
          colors: ['#1ccc48', '#EA5455'],
          plotOptions: {
            bar: {
              columnWidth: '20%',
              endingShape: 'rounded',
            },
          },
          xaxis: {
            labels: {
              style: {
                cssClass: 'text-grey fill-current',
              },
            },
            axisTicks: {
              show: false,
            },
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisBorder: {
              show: false,
            },
          },
          yaxis: {
            tickAmount: 5,
            labels: {
              style: {
                cssClass: 'text-grey fill-current',
              },
            },
          },
          tooltip: {
            x: { show: false },
          },
        },
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
      panel: 'year',
      future: false,
      panels: ['year'],
      show_calendar: false,
    };
  },
  created() {
    this.fetchData();
  },
  methods: {
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
      app.fetchData();
    },
    fetchData(){
      const app = this;
      const fetchResource = new Resource('dashboard/transaction-stats');
      const param = app.form;
      fetchResource.list(param).then(response => {
        app.clientRetentionBar.series = response.series;
      });
    },
  },
};
</script>
