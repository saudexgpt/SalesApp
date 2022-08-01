<template>
  <div v-if="page==='list'">
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex staffs-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Van Inventory</span>
        </div>
        <span style="float: right">
          <el-button
            round
            style="margin:0 0 20px 20px;"
            type="success"
            icon="el-icon-download"
            size="small"
            @click="handleDownload"
          >Export Van Inventory</el-button>
        </span>
        <vs-divider />
      </div>
      <!-- <div class="vx-col lg:w-1/4 w-full">
        <div class="flex staffs-end px-3">
          <span class="pull-right">
            <el-select
              v-model="selected_item_index"
              placeholder="Select Product"
              clearable
              style="width: 100%"
              class="filter-staff"
              filterable
              @change="viewByProduct"
            >
              <el-option
                v-for="(product, index) in products"
                :key="index"
                :label="product.name"
                :value="index"
              />
            </el-select>
          </span>
        </div>
      </div> -->
    </div>
    <v-client-table
      v-model="vanInventories"
      :columns="columns"
      :options="options"
    >
      <template slot="total_stocked" slot-scope="scope">
        <span>{{ scope.row.total_stocked }} {{ (scope.row.item.basic_unit) ? scope.row.item.basic_unit : scope.row.item.package_type }}</span>
      </template>
      <template slot="total_sold" slot-scope="scope">
        <span>{{ scope.row.total_sold }} {{ (scope.row.item.basic_unit) ? scope.row.item.basic_unit : scope.row.item.package_type }}</span>
      </template>
      <template slot="total_balance" slot-scope="scope">
        <span>{{ scope.row.total_balance }} {{ (scope.row.item.basic_unit) ? scope.row.item.basic_unit : scope.row.item.package_type }}</span>
      </template>
    </v-client-table>
  </div>
</template>

<script>
import moment from 'moment';
// import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
export default {
  props: {
    vanInventories: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      products: [],
      selected_item_index: '',
      sub_title: '',
      list: [],
      columns: [
        'staff.name',
        'item.name',
        'batch_no',
        'expiry_date',
        // 'total_stocked',
        // 'total_sold',
        'total_balance',
      ],

      options: {
        headings: {
          'staff.name': 'Rep',
          'item.name': 'Product',
          //   total_stocked: 'Total Stocked',
          //   total_sold: 'Total Sold',
          total_balance: 'Quantity',
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
        sortable: ['item.name'],
        filterable: ['item.name'],
      },
      page: 'list',
    };
  },
  methods: {
    moment,
    // checkPermission,
    handleDownload(){
      const app = this;
      app.$emit('download', app.vanInventories);
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
