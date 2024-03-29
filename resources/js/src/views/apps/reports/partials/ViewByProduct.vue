<template>
  <div v-loading="load_table" v-if="page==='list'">
    <div class="vx-row">
      <div class="vx-col lg:w-1/2 w-full">
        <div class="flex staffs-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Inventory {{ sub_title }}</span>
        </div>
        <vs-divider />
      </div>
      <div class="vx-col lg:w-1/2 w-full">
        <div class="flex staffs-end px-3">
          <span class="pull-right">
            <el-select v-model="team_id" filterable @change="viewByProduct">
              <el-option
                v-for="(team, index) in teams"
                :key="index"
                :label="team.name"
                :value="team.id"

              />
            </el-select>
            <el-select
              v-model="selected_item_index"
              placeholder="Select Product"
              clearable
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
            <!-- <el-button
                :loading="downloading"
                round
                class="filter-staff"
                type="primary"
                icon="el-icon-download"
                @click="handleDownload"
              >Export</el-button> -->
          </span>
        </div>
      </div>
    </div>
    <vs-tabs position="left" color="danger">
      <vs-tab label="Main Inventory">
        <div class="tab-text">
          <br>
          <span class="font-medium text-lg">Main Inventory</span>

          <v-client-table
            v-model="inventories"
            :columns="columns"
            :options="options"
          >
            <template slot="total_stocked" slot-scope="scope">
              <span>{{ scope.row.total_stocked }} {{ (scope.row.item.basic_unit) ? scope.row.item.basic_unit : scope.row.item.package_type }}</span>
            </template>
            <template slot="van_quantity" slot-scope="scope">
              <span>{{ scope.row.van_quantity }} {{ (scope.row.item.basic_unit) ? scope.row.item.basic_unit : scope.row.item.package_type }}</span>
            </template>
            <template slot="total_balance" slot-scope="scope">
              <span>{{ scope.row.total_balance }} {{ (scope.row.item.basic_unit) ? scope.row.item.basic_unit : scope.row.item.package_type }}</span>
            </template>
          </v-client-table>
        </div>
      </vs-tab>
      <vs-tab label="Van Inventory">
        <div class="tab-text">
          <br>
          <span class="font-medium text-lg">Van Inventory</span>
          <v-client-table
            v-model="sub_inventories"
            :columns="columns_2"
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
      </vs-tab>
    </vs-tabs>
  </div>
</template>

<script>
import moment from 'moment';
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
const productsResource = new Resource('products');
const productInventoryResource = new Resource('inventory/view-by-product');
export default {
  name: 'Customers',
  components: { Pagination },
  directives: { permission },
  data() {
    return {
      products: [],
      selected_item_index: '',
      sub_title: '',
      inventories: [],
      sub_inventories: [],
      columns: [
        'staff.name',
        'item.name',
        // 'total_stocked',
        // 'van_quantity',
        'total_balance',
      ],
      columns_2: [
        'staff.name',
        'item.name',
        // 'total_stocked',
        // 'total_sold',
        'total_balance',
      ],

      options: {
        headings: {
          'staff.name': 'Rep',
          'item.name': 'Product',
          total_stocked: 'Total Stocked',
          total_sold: 'Total Sold',
          van_quantity: 'Van Quantity',
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
        sortable: ['staff.name'],
        filterable: ['staff.name'],
      },
      page: 'list',
      teams: [],
      team_id: '',
    };
  },
  created() {
    this.fetchTeams();
    this.fetchProducts();
  },
  methods: {
    moment,
    checkPermission,
    fetchTeams() {
      const app = this;
      // this.load_table = true;
      const salesRepResource = new Resource('teams');
      salesRepResource
        .list()
        .then((response) => {
          app.teams = response.teams;
          if (app.teams.length > 0) {
            app.team_id = app.teams[0].id;
          }
        })
        .catch((error) => {
          console.log(error);
        });
    },
    fetchProducts() {
      this.load_table = true;
      productsResource
        .list()
        .then((response) => {
          this.products = response.items;
          this.load_table = false;
        })
        .catch((error) => {
          console.log(error);
          this.load_table = false;
        });
    },
    viewByProduct() {
      const app = this;
      const product = app.products[app.selected_item_index];
      const team_id = app.team_id;
      const param = { team_id: team_id, item_id: product.id };
      app.sub_title = 'of ' + product.name;
      if (team_id === '') {
        app.$alert('Please select a team');
        return;
      }
      if (app.selected_item_index === '') {
        app.$alert('Please select a product');
        return;
      }
      app.$vs.loading();
      productInventoryResource
        .list(param)
        .then((response) => {
          app.inventories = response.inventories;
          app.sub_inventories = response.sub_inventories;
          app.$vs.loading.close();
        })
        .catch((error) => {
          console.log(error);
          app.$vs.loading.close();
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
