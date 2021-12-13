<template>
  <el-card v-loading="load">
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex staffs-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Warehouse Inventory</span>
        </div>
        <vs-divider />
      </div>
      <div class="vx-col lg:w-3/4 w-full">
        <el-button
          round
          class="filter-item"
          type="success"
          icon="el-icon-home"
          @click="stockProductsFromWarehouse"
        >Get Warehouse Supplies
        </el-button>
      </div>
    </div>
    <v-client-table
      v-model="warehouse_stocks"
      :columns="columns"
      :options="options"
    >
      <template slot="quantity_supplied" slot-scope="scope">
        <span>{{ scope.row.quantity_supplied + ' ' + scope.row.item.package_type }}</span>
      </template>
      <template slot="quantity_approved" slot-scope="scope">
        <span>{{ scope.row.quantity_approved + ' ' + scope.row.item.package_type }}</span>
      </template>
      <template slot="action" slot-scope="props">
        <el-tooltip class="item" effect="dark" content="Approve stock received from warehouse" placement="top-start">
          <el-button
            v-if="checkRole(['sales_rep'])"
            round
            class="filter-item"
            type="danger"
            @click="approveProduct(props.index, props.row)"
          >Approve Receipt
          </el-button>
        </el-tooltip>
      </template>
    </v-client-table>
  </el-card>
</template>

<script>
import moment from 'moment';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
// import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
export default {
  data() {
    return {
      warehouse_stocks: [],
      selected_item_index: '',
      sub_title: '',
      list: [],
      columns: [
        'item.name',
        'batch_no',
        'quantity_supplied',
        'quantity_approved',
        'action',
      ],

      options: {
        headings: {
          action: '',
          'item.name': 'Product',
          quantity_supplied: 'Quantity Stocked',
          quantity_approved: 'Quantity Approved',
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
      load: false,
    };
  },
  created() {
    this.showWarehouseStocks();
  },
  methods: {
    moment,
    checkRole,
    stockProductsFromWarehouse() {
      const app = this;
      app.load = true;
      const stockResource = new Resource('inventory/stock-product-from-warehouse');
      stockResource.store()
        .then(response => {
          app.showWarehouseStocks();
          this.$message({
            type: 'success',
            message: 'Warehouse Stocks Loaded Successfully',
          });
        });
    },
    showWarehouseStocks() {
      const app = this;
      app.load = true;
      const stockResource = new Resource('inventory/warehouse-stock');
      stockResource.list()
        .then(response => {
          app.load = false;
          app.warehouse_stocks = response.warehouse_stocks;
        });
    },

    approveProduct(index, row) {
      const balance = row.quantity_supplied - row.quantity_approved;
      this.$prompt('Please input quantity received', 'Enter Quantity Received', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        inputType: 'number',
        inputValue: balance,
        inputValidator: function(value) {
          return balance >= value;
        },
        inputErrorMessage: 'Quantity MUST NOT be more than ' + balance,
      }).then(({ value }) => {
        if (balance >= value) {
          const stockVanResource = new Resource('inventory/accept-warehouse-products');
          const param = { quantity: value };
          stockVanResource.update(row.id, param)
            .then(response => {
              this.warehouse_stocks = response.warehouse_stocks;
              this.$message({
                type: 'success',
                message: 'Main Stock updated Successfully',
              });
            });
        } else {
          this.$alert('Quantity should not be more than ' + balance);
        }
      }).catch(() => {
        this.$message({
          type: 'info',
          message: 'Van Stocking canceled',
        });
      });
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
