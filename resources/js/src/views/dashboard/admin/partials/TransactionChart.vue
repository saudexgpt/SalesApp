<template>
  <vx-card v-loading="load" title="Transactions">

    <!-- <div class="vx-col lg:w-1/4 w-full">
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
    </div> -->
    <filter-options @submitQuery="fetchData" />
    <vue-apex-charts :options="clientRetentionBar.chartOptions" :series="clientRetentionBar.series" type="bar" height="277" />
  </vx-card>
</template>
<script>

import VueApexCharts from 'vue-apexcharts';
import Resource from '@/api/resource';
import FilterOptions from '@/views/apps/reports/FilterOptions';
export default {
  components: { VueApexCharts, FilterOptions },
  data() {
    return {
      clientRetentionBar: {
        series: [{
          name: 'Sales',
          data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        }, {
          name: 'Collections',
          data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        }],
        chartOptions: {
          stroke: {
            lineCap: 'round',
          },
          plotOptions: {
            bar: {
              borderRadius: 0,
              columnWidth: '50%',
              dataLabels: {
                position: 'bottom', // top, center, bottom
              },
            },
          },
          grid: {
            borderColor: '#ebebeb',
            padding: {
              left: 0,
              right: 0,
            },
          },
          legend: {
            show: true,
          },
          dataLabels: {
            enabled: false,
            formatter: function(val) {
              return '₦' + val;
            },
            offsetY: -20,
          },
          chart: {
            stacked: false,
            type: 'bar',
            toolbar: { show: false },
          },
          colors: ['#111111', '#1ccc48'],
          xaxis: {
            labels: {
              style: {
                cssClass: 'text-grey fill-current',
              },
            },
            axisTicks: {
              show: true,
            },
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisBorder: {
              show: false,
            },
          },
          yaxis: {
            tickAmount: 5,
            labels: {
              show: true,
              formatter: function(val) {
                return val;
              },
              style: {
                colors: ['#000000'],
              },
            },
            title: {
              text: 'Amount (₦)',
              rotate: -90,
              offsetX: 0,
              offsetY: 0,
              style: {
                color: '#000000',
                fontSize: '12px',
                fontFamily: 'Helvetica, Arial, sans-serif',
                fontWeight: 600,
                cssClass: 'apexcharts-yaxis-title',
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
      load: false,
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
    fetchData(param){
      const app = this;
      app.load = true;
      app.form = param;
      const fetchResource = new Resource('dashboard/transaction-stats');
      fetchResource.list(param).then(response => {
        app.clientRetentionBar.series = response.series;
        app.load = false;
      }).catch(() => {
        app.load = false;
      });
    },
  },
};
</script>
