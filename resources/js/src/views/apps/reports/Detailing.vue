<template>
  <vx-card>
    <div v-if="page === 'list'">
      <div slot="header" class="clearfix">
        <feather-icon svg-classes="w-6 h-6" icon="HomeIcon" class="mr-2" />
        <strong class="font-medium text-lg">Detailing Report {{ sub_title }}</strong>
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
        <v-client-table v-model="detailings" :columns="detailings_columns" :options="detailings_options">
          <div
            slot="customer.business_name"
            slot-scope="props">
            {{ props.row.customer.business_name }} <br>
            <small>{{ props.row.customer.address }}</small>
          </div>
          <div
            slot="ratings"
            slot-scope="{row}">
            <el-progress :text-inside="true" :stroke-width="20" :percentage="row.ratings * 10" :color="ratingStatus(row.ratings * 10)" />
          </div>

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
          @pagination="fetchReports(form)"
        />
      </el-row>
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
      detailings: [],
      detailings_columns: [
        'item.name',
        'ratings',
        'customer.business_name',
        'visit.visited_by.name',
        'created_at',
      ],

      detailings_options: {
        headings: {
          'customer.business_name': 'Customer',
          'item.name': 'Product',
          ratings: 'Customer\'s Product Ratings',
          'visit.visited_by.name': 'Detailed By',
          created_at: 'Date',
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
        sortable: ['customer.business_name', 'item.name', 'created_at'],
        filterable: ['customer.business_name', 'item.name', 'created_at'],
      },
      load: false,
      load_customer: false,
      total: 0,
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
      app.form = param;
      app.load = true;
      const { limit, page } = app.form;
      app.detailings_options.perPage = limit;
      const detailingsResource = new Resource('visits/fetch-detailed-products');
      // const param = app.form;
      detailingsResource.list(param)
        .then(response => {
          app.detailings = response.detailings.data;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.detailings.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.detailings.total;
          app.load = false;
        });
    },
    ratingStatus(ratings) {
      if (ratings < 30) {
        return '#C03639';
      } else if (ratings < 50) {
        return '#e6a23c';
      } else if (ratings < 70) {
        return '#909399';
      } else if (ratings < 90) {
        return '#409eff';
      } else {
        return '#67c23a';
      }
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['DETAILED PRODUCTS ' + this.sub_title, '', '', '', '']];
        const tHeader = [
          'PRODUCT',
          'RATINGS',
          'CUSTOMER',
          'DETAILED BY',
          'DATE',
        ];
        const filterVal = [
          'item.name',
          'ratings',
          'customer.business_name',
          'visit.visited_by.name',
          'created_at',
        ];
        const list = this.detailings;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'DETAILED PRODUCTS',
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
          if (j === 'item.name') {
            return (v['item']) ? v['item']['name'] : '';
          }
          if (j === 'visit.visited_by.name') {
            return (v['visit']) ? v['visit']['visited_by']['name'] : '';
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
