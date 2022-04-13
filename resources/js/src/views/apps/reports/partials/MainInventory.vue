<template>
  <div v-if="page==='list'">
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex staffs-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Main Inventory</span>
        </div>
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
      v-model="inventories"
      :columns="columns"
      :options="options"
    >
      <!-- <template slot="total_stocked" slot-scope="scope">
        <span>{{ scope.row.total_stocked + ' ' + scope.row.item.package_type }}</span>
      </template>
      <template slot="van_quantity" slot-scope="scope">
        <span>{{ scope.row.van_quantity + ' ' + scope.row.item.package_type }}</span>
      </template> -->
      <template slot="total_balance" slot-scope="scope">
        <span>{{ scope.row.total_balance + ' ' + scope.row.item.package_type }}</span>
      </template>
      <template slot="action" slot-scope="props">
        <el-button
          v-if="checkRole(['sales_rep'])"
          round
          class="filter-item"
          type="danger"
          @click="stockVan(props.index, props.row)"
        >Stock Van
        </el-button>
      </template>
    </v-client-table>
  </div>
</template>

<script>
import moment from 'moment';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
// import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
export default {
  props: {
    inventories: {
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
        'action',
        'item.name',
        // 'total_stocked',
        // 'van_quantity',
        'total_balance',
      ],

      options: {
        headings: {
          action: '',
          'item.name': 'Product',
          //   total_stocked: 'Total Stocked',
          //   van_quantity: 'Moved To Van',
          total_balance: 'Total Balance',
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
    checkRole,
    stockVan(index, row) {
      const balance = parseInt(row.total_balance);
      this.$prompt('Please input quantity to stock', 'Enter Quantity', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        inputType: 'number',
        inputValue: balance,
        // inputValidator: function(value) {
        //   return balance >= value;
        // },
        // inputErrorMessage: 'Overflow Value',
      }).then(({ value }) => {
        const quantity = parseInt(value);
        if (balance >= value) {
          const stockVanResource = new Resource('inventory/stock-van');
          const param = { quantity: quantity };
          stockVanResource.update(row.id, param)
            .then(response => {
              // this.inventories = response;
              this.$emit('update', response);
              this.$message({
                type: 'success',
                message: 'Van Stocked Successfully',
              });
            });
        } else {
          this.$alert('Quantity should not be more than ' + balance);
        }
      }).catch(() => {
        // this.$message({
        //   type: 'info',
        //   message: 'Van Stocking canceled',
        // });
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
