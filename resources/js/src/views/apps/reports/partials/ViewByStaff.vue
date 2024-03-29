<template>
  <div v-loading="load_table" v-if="page==='list'">
    <div class="vx-row">
      <div class="vx-col lg:w-1/2 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Inventory of Products {{ sub_title }}</span>
        </div>
        <vs-divider />
      </div>
      <div class="vx-col lg:w-1/2 w-full">
        <div class="flex items-end px-3">
          <span class="pull-right">
            <el-select v-model="team_id" filterable @change="fetchTeamReps">
              <el-option
                v-for="(team, index) in teams"
                :key="index"
                :label="team.name"
                :value="team.id"

              />
            </el-select>
            <el-select
              v-model="selected_staff_index"
              placeholder="Select Staff"
              clearable
              class="filter-item"
              filterable
              @change="viewByStaff"
            >
              <el-option
                v-for="(rep, index) in sales_reps"
                :key="index"
                :label="rep.name"
                :value="index"
              />
            </el-select>
            <!-- <el-button
                :loading="downloading"
                round
                class="filter-item"
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
          <main-inventory :inventories="inventories" @download="downloadMain" />
        </div>
      </vs-tab>
      <vs-tab label="Van Inventory">
        <div class="tab-text">
          <br>
          <van-inventory :van-inventories="sub_inventories" @download="downloadVan" />
        </div>
      </vs-tab>
    </vs-tabs>
    <vs-popup :active.sync="popupActive" :title="details_title">
      <inventory-detail v-if="popupActive" :selected-item="selected_detail_item" />
    </vs-popup>
  </div>
</template>

<script>
import moment from 'moment';
import MainInventory from './MainInventory';
import VanInventory from './VanInventory';
import InventoryDetail from './InventoryDetail'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
// const salesRepResource = new Resource('users/fetch-sales-reps');
const staffResource = new Resource('inventory/view-by-staff');
export default {
  name: 'Customers',
  components: { InventoryDetail, MainInventory, VanInventory },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      selected_staff_index: '',
      sub_title: '',
      inventories: [],
      sub_inventories: [],
      load_table: false,
      page: 'list',
      popupActive: false,
      details_title: '',
      selected_detail_item: '',
      teams: [],
      team_id: '',
    };
  },
  created() {
    this.fetchTeams();
  },
  methods: {
    moment,
    checkPermission,
    showInventoryDetails(selected_item) {
      const app = this;
      app.details_title = 'Stock Details for ' + selected_item.item.name;
      app.selected_detail_item = selected_item;
      app.popupActive = true;
    },
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
            app.fetchTeamReps(app.team_id);
          }
        })
        .catch((error) => {
          console.log(error);
        });
    },
    fetchTeamReps(teamId) {
      const app = this;
      // this.load_table = true;
      const salesRepResource = new Resource('teams/fetch-reps');
      salesRepResource
        .list({ team_id: teamId })
        .then((response) => {
          app.sales_reps = response.team_reps;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    // fetchSalesReps() {
    //   this.load_table = true;
    //   salesRepResource
    //     .list()
    //     .then((response) => {
    //       this.sales_reps = response.sales_reps;
    //       this.load_table = false;
    //     })
    //     .catch((error) => {
    //       console.log(error);
    //       this.load_table = false;
    //     });
    // },
    viewByStaff() {
      const app = this;
      const staff = app.sales_reps[app.selected_staff_index];
      const param = { staff_id: staff.id };
      app.sub_title = 'for ' + staff.name;
      app.$vs.loading();
      staffResource
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
    downloadVan(data){
      const app = this;
      app.download(data, 'Van');
    },
    downloadMain(data){
      const app = this;
      app.download(data, 'Main');
    },
    download(export_data, type) {
      import('@/vendor/Export2Excel').then((excel) => {
        // const multiHeader = [[`${type} Inventory ${this.sub_title}`, '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          // 'REP',
          'PRODUCT',
          'BATCH NO',
          'EXPIRY DATE',
          'QUANTITY',
        ];
        const filterVal = [
          // 'staff.name',
          'item.name',
          'batch_no',
          'expiry_date',
          'total_balance',
        ];
        const data = this.formatJson(filterVal, export_data);
        excel.export_json_to_excel({
          // multiHeader,
          header: tHeader,
          data,
          filename: `${type} Inventory ${this.sub_title}`,
          autoWidth: true,
          bookType: 'csv',
        });
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'item.name') {
            const package_type = (v['item']['basic_unit']) ? v['item']['basic_unit'] : v['item']['package_type']
            return v['item']['name'] + ' ' + package_type;
          }
          //   if (j === 'staff.name') {
          //     return v['staff']['name'];
          //   }
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
