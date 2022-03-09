<template>
  <div>
    <vx-card v-loading="load_table" v-if="page==='list'">
      <div class="vx-row">
        <div class="vx-col lg:w-3/4 w-full">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
            <span class="font-medium text-lg">List of Customers</span>
          </div>
          <vs-divider />
        </div>
        <div class="vx-col lg:w-1/4 w-full">
          <div class="flex items-end px-3">
            <span class="pull-right">
              <router-link
                :to="'/customers/map'"
              >
                <el-button
                  round
                  class="filter-item"
                  type="default"
                  icon="el-icon-map-location"
                >Map
                </el-button>
              </router-link>
              <el-button
                :loading="downloading"
                round
                class="filter-item"
                type="primary"
                icon="el-icon-download"
                @click="handleDownload"
              >Export</el-button>
            </span>
          </div>
        </div>
      </div>
      <div class="filter-container">
        <!-- <el-row v-if="checkPermission(['assign-field-staff'])" :gutter="20">
          <el-col :xs="24" :sm="24" :md="24">
            <h4>Assign Rep to Customers</h4>
            <aside>
              <el-select
                v-model="assignRep.relating_officer"
                placeholder="Select Rep"
                filterable
              >
                <el-option
                  v-for="(rep, index) in sales_reps"
                  :key="index"
                  :label="rep.name"
                  :value="rep.id"
                />
              </el-select>
              <el-select
                v-model="assignRep.customer_ids"
                placeholder="Select Customers"
                multiple
                filterable
                collapse-tags
              >
                <el-option
                  v-for="(customer, customer_index) in list"
                  :key="customer_index"
                  :label="customer.business_name"
                  :value="customer.id"
                />
              </el-select>
              <el-button type="primary" @click="assignOfficer()">Assign</el-button>
            </aside>
          </el-col>
        </el-row> -->
        <el-row :gutter="20">
          <el-col :xs="24" :sm="12" :md="12">
            <el-input
              v-model="query.keyword"
              placeholder="Search User"
              style="width: 200px"
              class="filter-item"
              @input="handleFilter"
            />
          </el-col>
        </el-row>
      </div>
      <v-client-table
        v-model="list"
        :columns="columns"
        :options="options"
      >

        <template slot="assigned_officer.name" slot-scope="props">
          <span>{{ props.row.assigned_officer.name }}</span>
        </template>
        <template slot="visits" slot-scope="scope">
          <span>{{ (scope.row.visits.length > 0) ? moment(scope.row.visits[0].visit_date).format('ll') : '' }}</span>
        </template>
        <template slot="created_at" slot-scope="scope">
          <span>{{ moment(scope.row.created_at).format('ll') }}</span>
        </template>
        <template slot="date_verified" slot-scope="scope">
          <span>{{ (scope.row.date_verified) ? moment(scope.row.date_verified).format('ll') : '' }}</span>
        </template>
        <template slot="action" slot-scope="scope">
          <el-tooltip
            class="item"
            effect="dark"
            content="View Customer Details"
            placement="top-start"
          >
            <router-link
              :to="'/customer/details/' + scope.row.id"
            >
              <el-button
                round
                type="success"
                size="small"
                icon="el-icon-view"
              />
            </router-link>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            content="View Customer Statement"
            placement="top-start"
          >
            <router-link
              :to="'/report/customer-statement/' + scope.row.id"
            >
              <el-button
                round
                type="warning"
                size="small"
                icon="el-icon-document"
              />
            </router-link>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            content="Edit Customer"
            placement="top-start"
          >
            <el-button
              v-permission="['update-customers']"
              round
              type="primary"
              size="small"
              icon="el-icon-edit"
              @click="setEditCustomerDetails(scope.row)"
            />
          </el-tooltip>
        </template>
      </v-client-table>
      <el-row :gutter="20">
        <pagination
          v-show="total > 0"
          :total="total"
          :page.sync="query.page"
          :limit.sync="query.limit"
          @pagination="getList"
        />
      </el-row>

      <vs-popup
        :active.sync="dialogFormVisible"
        fullscreen
        title="Edit Customer Details">
        <div v-loading="updatingCustomer" class="con-exemple-prompt">
          <form >
            <div class="vx-row">
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <vs-input v-validate="'required'" v-model="selectedCustomer.business_name" name="business_name" label-placeholder="Business Name" class="mt-3 w-full" data-vv-validate-on="blur"/>
                <span v-show="errors.has('business_name')" class="text-danger text-sm">{{ errors.first('business_name') }}</span>
              </div>
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <label>Select Customer Type</label>
                <el-select
                  v-model="selectedCustomer.customer_type_id"
                  placeholder="Select Customer Type"
                  filterable
                  style="width: 100%"
                >
                  <el-option
                    v-for="(customer_type, type_index) in customer_types"
                    :key="type_index"
                    :label="customer_type.name"
                    :value="customer_type.id"
                  />
                </el-select>
              </div>
            </div>
            <div class="vx-row">
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <gmap-autocomplete
                  class="form-control"
                  placeholder="Customer Address"
                  @place_changed="getAddressData" />
                  <!-- <vue-google-autocomplete
                  id="map"
                  ref="address"
                  :country="['ng']"
                  class="mt-3 w-full"
                  placeholder="Customer Address"
                  @placechanged="getAddressData"
                /> -->
              </div>
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <ol>
                  <li><strong>Address:</strong> {{ selectedCustomer.address }}</li>
                  <li><strong>Street:</strong> {{ selectedCustomer.street }}</li>
                  <li><strong>Area:</strong> {{ selectedCustomer.area }}</li>
                  <li><strong>Lat:</strong> {{ selectedCustomer.latitude }}</li>
                  <li><strong>Lng:</strong> {{ selectedCustomer.longitude }}</li>
                </ol>
              </div>
            </div>
            <div class="vx-row">
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <el-select
                  v-model="selected_state"
                  placeholder="Select State"
                  filterable
                  style="width: 100%"
                  @input="setStateLGAs()"
                >
                  <el-option
                    v-for="(state, state_index) in states"
                    :key="state_index"
                    :label="state.name"
                    :value="state_index"
                  />
                </el-select>
              </div>
              <div class="vx-col sm:w-1/2 w-full mb-2">
                <el-select
                  v-model="selectedCustomer.lga_id"
                  placeholder="Select LGA"
                  style="width: 100%"
                  filterable
                >
                  <el-option
                    v-for="(lga, lga_index) in lgas"
                    :key="lga_index"
                    :label="lga.name"
                    :value="lga.id"
                  />
                </el-select>
              </div>
            </div>

            <div class="dialog-footer">
              <vs-button color="danger" type="filled" @click="dialogFormVisible = false">Cancel</vs-button>
              <vs-button color="success" type="filled" @click.prevent="updateCustomer()">Submit</vs-button>
            </div>
          </form>
        </div>
      </vs-popup>
    </vx-card>
  </div>
</template>

<script>
import moment from 'moment';
import VueGoogleAutocomplete from 'vue-google-autocomplete';
import Pagination from '@/components/Pagination'; // Secondary package based on el-pagination
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
const customersResource = new Resource('customers');
export default {
  name: 'Customers',
  components: { Pagination, VueGoogleAutocomplete },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      list: [],
      columns: [
        'business_name',
        'customer_type.name',
        'address',
        'area',
        'visits',
        'registrar.name',
        'assigned_officer.name',
        // 'verifier.name',
        'created_at',
        'date_verified',
        'action',
      ],

      options: {
        headings: {
          business_name: 'Name',
          'customer_type.name': 'Type',
          'visits': 'Last Visit',
          'registrar.name': 'Created By',
          'assigned_officer.name': 'Field Staff',
          'verifier.name': 'Verified By',
        },
        pagination: {
          dropdown: true,
          chunk: 100,
        },
        perPage: 100,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['business_name', 'customer_type.name', 'area', 'created_at', 'date_verified'],
        filterable: ['business_name', 'customer_type.name', 'area', 'registrar.name', 'assigned_officer.name'],
      },
      total: 0,
      loading: false,
      load_table: false,
      downloading: false,
      updatingCustomer: false,
      query: {
        page: 1,
        limit: 100,
        keyword: '',
        role: '',
      },
      selectedCustomer: {},
      dialogFormVisible: false,
      selected_customer: '',
      page: 'list',
      assignRep: {
        relating_officer: '',
        customer_ids: [],
      },
      selected_state: '',
      states: [],
      lgas: [],
      customer_types: [],
    };
  },
  created() {
    this.getList();
    this.fetchSalesRep();
  },
  methods: {
    moment,
    checkPermission,
    getAddressData(addressData) {
      // console.log(addressData);
      // console.log(placeResultData)
      // console.log(id)
      const app = this;
      app.selectedCustomer.address = addressData.formatted_address;
      app.selectedCustomer.longitude = addressData.geometry.location.lng();
      app.selectedCustomer.latitude = addressData.geometry.location.lat();
      const address_components = addressData.address_components;
      address_components.forEach(element => {
        if (element.types[0] === 'route') {
          app.selectedCustomer.street = element.long_name;
        }
        if (element.types[0] === 'administrative_area_level_2') {
          app.selectedCustomer.area = element.long_name;
        }
      });
    },
    setEditCustomerDetails(row){
      this.selectedCustomer = row;
      this.selected_state = this.states.map(object => object.id).indexOf(this.selectedCustomer.state_id);
      this.setStateLGAs();
      this.dialogFormVisible = true;
    },
    setStateLGAs() {
      const app = this;
      app.lga = [];
      app.lgas = app.states[app.selected_state].lgas;
      app.selectedCustomer.state_id = app.states[app.selected_state].id;
    },
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
        });
    },
    getList() {
      const { limit, page } = this.query;
      this.options.perPage = limit;
      this.load_table = true;
      customersResource
        .list(this.query)
        .then((response) => {
          this.states = response.states;
          this.customer_types = response.customer_types;
          const customers = response.customers;
          this.list = customers.data;
          this.list.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = customers.total;
          this.load_table = false;
        })
        .catch((error) => {
          console.log(error);
          this.load_table = false;
        });
    },
    assignOfficer() {
      const app = this;
      const staffId = app.assignRep.relating_officer;
      const customer_ids = app.assignRep.customer_ids;
      const assignOfficerResource = new Resource('customers/assign-field-staff');
      assignOfficerResource
        .update(staffId, { customer_ids: customer_ids })
        .then(() => {
          app.assignRep = {
            relating_officer: '',
            customer_ids: [],
          };
          app.$message('Officer Assigned Successfully');
          app.getList();
        })
        .catch((error) => {
          console.log(error);
        });
    },
    updateCustomer() {
      const app = this;
      app.updatingCustomer = true;
      const selectedCustomer = app.selectedCustomer;
      const assignOfficerResource = new Resource('customers/update');
      assignOfficerResource
        .update(selectedCustomer.id, selectedCustomer)
        .then(() => {
          app.selectedCustomer = {};
          app.dialogFormVisible = false;
          app.updatingCustomer = false;
          app.$message('Customer Updated Successfully');
          app.getList();
        })
        .catch((error) => {
          app.updatingCustomer = false;
          console.log(error);
        });
    },
    showCustomerDetails(selectedCustomer){
      const app = this;
      app.selected_customer = selectedCustomer;
      app.page = 'details';
    },
    handleCreate() {
      this.resetNewUser();
      this.dialogFormVisible = true;
      this.$nextTick(() => {
        this.$validator.reset();
      });
    },
    handleFilter() {
      this.query.page = 1;
      this.getList();
    },
    handleDownload(){
      // fetch all data for export
      this.query.limit = this.total;
      this.downloading = true;
      customersResource.list(this.query)
        .then(response => {
          this.export(response.data);

          this.downloading = false;
        });
    },
    export(export_data) {
      import('@/vendor/Export2Excel').then((excel) => {
        const tHeader = [
          'name',
          'email',
          'phone',
          'address',
        ];
        const filterVal = [
          'name',
          'email',
          'phone',
          'address',
        ];
        const data = this.formatJson(filterVal, export_data);
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'user-list',
        });
        this.downloading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'role') {
            return v['roles'].join(', ');
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
