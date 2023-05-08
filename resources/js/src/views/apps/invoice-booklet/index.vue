<template>
  <div>
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Inventory Booklets</span>
        </div>
        <vs-divider />
      </div>
      <div class="vx-col lg:w-1/4 w-full">
        <div class="flex items-end px-3">
          <span class="pull-right">
            <el-button
              round
              style="margin:0 0 20px 20px;"
              type="success"
              icon="el-icon-plus"
              @click="popupActive = true"
            >Create</el-button>
          </span>
        </div>
      </div>
      <div class="vx-col lg:w-3/4 w-full">
        <el-select
          v-model="selected_staff"
          value-key="id"
          placeholder="Select Staff"
          clearable
          style="width: 100%"
          class="filter-item"
          filterable
          @input="fetchBooklets()"
        >
          <el-option
            label="All"
            value="{id: all}"
          />
          <el-option
            v-for="(rep, index) in sales_reps"
            :key="index"
            :label="rep.name"
            :value="rep"
          />
        </el-select>
      </div>
    </div>

    <v-client-table
      v-model="invoice_booklets"
      :columns="columns"
      :options="options"
    >
      <template slot="range" slot-scope="scope">
        <span>{{ `${scope.row.lower_limit} - ${scope.row.upper_limit}` }}</span>
      </template>
      <template slot="unused_invoice_numbers" slot-scope="scope">
        <el-input v-model="scope.row.unused_invoice_numbers" type="textarea" readonly />
      </template>
      <template slot="skipped_invoice_numbers" slot-scope="scope">
        <el-input v-model="scope.row.skipped_invoice_numbers" type="textarea" readonly />
      </template>
      <template slot="created_at" slot-scope="scope">
        <span>{{ moment(scope.row.created_at).format('ll') }}</span>
      </template>
    </v-client-table>
    <el-row :gutter="20">
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="query.page"
        :limit.sync="query.limit"
        @pagination="fetchBooklets"
      />
    </el-row>
    <vs-popup
      :active.sync="popupActive"
      fullscreen
      title="Create Invoice Booklet">
      <add-booklet v-if="popupActive" :reps="sales_reps" />
    </vs-popup>
  </div>
</template>

<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import AddBooklet from './AddBooklet';// Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
const salesRepResource = new Resource('users/fetch-sales-reps');
export default {
  name: 'Customers',
  components: { Pagination, AddBooklet },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      selected_staff: '',
      invoice_booklets: [],
      columns: [
        'rep.name',
        'range',
        'unused_invoice_numbers',
        'skipped_invoice_numbers',
      ],

      options: {
        headings: {
          'rep.name': 'Rep',
          range: 'Range',
          skipped_invoice_numbers: 'Skipped Invoice No.',
          unused_invoice_numbers: 'Unused Invoice No.',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['rep.name', 'range'],
        filterable: ['rep.name', 'range'],
      },
      query: {
        page: 1,
        limit: 10,
      },
      total: 0,
      popupActive: false,
      details_title: '',
      selected_detail_item: '',
    };
  },
  created() {
    this.fetchSalesReps();
  },
  methods: {
    moment,
    checkPermission,
    fetchSalesReps() {
      salesRepResource
        .list()
        .then((response) => {
          this.sales_reps = response.sales_reps;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    fetchBooklets() {
      const app = this;
      const { limit, page } = this.query;
      this.options.perPage = limit;
      const staff = app.selected_staff;
      const staffResource = new Resource('invoice-booklets');
      const param = app.query;
      param.rep_id = staff.id;
      app.$vs.loading();

      staffResource
        .list(param)
        .then((response) => {
          this.invoice_booklets = response.invoice_booklets.data;
          this.invoice_booklets.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.invoice_booklets.total;
          app.$vs.loading.close();
        })
        .catch((error) => {
          console.log(error);
          app.$vs.loading.close();
        });
    },
    handleDownload(){
      const app = this;
      app.export(app.invoice_booklets);
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
          'PRODUCT',
          'TOTAL STOCKED',
          'TOTAL SOLD',
          'TOTAL BALANCE',
        ];
        const filterVal = [
          'item.name',
          'total_stocked',
          'total_sold',
          'total_balance',
        ];
        const data = this.formatJson(filterVal, export_data);
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'inventory-by-staff',
        });
        this.downloading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'item.name') {
            return v['item']['name'];
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
