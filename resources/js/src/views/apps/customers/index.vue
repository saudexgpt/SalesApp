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
                  type="danger"
                  icon="el-icon-map-location"
                >Map
                </el-button>
              </router-link>
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
          <el-col :xs="24" :sm="8" :md="8">
            Search Customer
            <el-input
              v-model="query.keyword"
              placeholder="Search Customer"
              style="width: 100%"
              class="filter-item"
              @input="handleFilter"
            />
          </el-col>
          <el-col :xs="24" :sm="8" :md="8">
            Filter by Rep
            <el-select
              v-model="query.rep_id"
              placeholder="Select Rep"
              filterable
              style="width: 100%"
              @input="getList()"
            >
              <el-option
                v-for="(rep, index) in sales_reps"
                :key="index"
                :label="rep.name"
                :value="rep.id"
              />
            </el-select>
          </el-col>
          <el-col :xs="24" :sm="8" :md="8">
            Filter by Verification
            <el-select
              v-model="query.verify_type"
              placeholder="Filter by Verification"
              style="width: 100%"
              @input="getList()"
            >
              <el-option label="All" value="all"/>
              <el-option label="Verified" value="verified"/>
              <el-option label="Unverified" value="unverified"/>
            </el-select>
            <!-- <el-radio-group v-model="query.verify_type" @change="getList()">
              <el-radio label="all" border>All</el-radio>
              <el-radio label="verified" border>Verified</el-radio>
              <el-radio label="unverified" border>Unverified</el-radio>
            </el-radio-group> -->
          </el-col>
        </el-row>
      </div>
      <div>
        <el-button
          :loading="downloading"
          round
          class="filter-item"
          type="primary"
          icon="el-icon-download"
          @click="handleDownload"
        >Export</el-button>
        <v-client-table
          v-model="list"
          :columns="columns"
          :options="options"
        >

          <template slot="assigned_officer" slot-scope="props">
            <el-select
              v-if="checkPermission(['assign-field-staff'])"
              v-model="props.row.relating_officer"
              placeholder="Select Rep"
              filterable
              @input="assignOfficer($event, props.row.id)"
            >
              <el-option
                v-for="(rep, index) in sales_reps"
                :key="index"
                :label="rep.name"
                :value="rep.id"
              />
            </el-select>
            <span v-else>{{ (props.row.assigned_officer !== null) ? props.row.assigned_officer.name : 'Not Assigned' }}</span>
          </template>
          <template slot="visits" slot-scope="scope">
            <span>{{ (scope.row.visits.length > 0) ? moment(scope.row.visits[0].visit_date).format('ll') : 'Not visited' }}</span>
          </template>
          <template slot="created_at" slot-scope="scope">
            <span>{{ moment(scope.row.created_at).format('ll') }}</span>
          </template>
          <template slot="date_verified" slot-scope="scope">
            <span>{{ (scope.row.date_verified) ? moment(scope.row.date_verified).format('ll') : '' }}</span>
          </template>
          <template slot="action" slot-scope="scope">
            <el-dropdown>
              <el-button type="primary">
                Action<i class="el-icon-arrow-down el-icon--right"/>
              </el-button>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item>
                  <router-link
                    :to="'/customer/details/' + scope.row.id"
                  >
                    <el-button
                      icon="el-icon-document"
                    >
                      View Details
                    </el-button>
                  </router-link>
                </el-dropdown-item>
                <el-dropdown-item
                  v-permission="['update-customers']"
                  divided
                >

                  <el-button
                    icon="el-icon-edit"
                    @click="setEditCustomerDetails(scope.row)"
                  >
                    Edit Customer
                  </el-button>
                </el-dropdown-item>
                <el-dropdown-item divided>
                  <router-link
                    :to="'/report/customer-statement/' + scope.row.id"
                  >
                    <el-button
                      icon="el-icon-document"
                    >
                      View Statement
                    </el-button>
                  </router-link>
                </el-dropdown-item>
                <el-dropdown-item
                  v-permission="['verify-customers']"
                  v-if="scope.row.date_verified === null"
                  divided
                >

                  <el-button
                    icon="el-icon-check"
                    @click="verifyCustomer(scope.index, scope.row.id, scope.row.business_name)"
                  >
                    Verify Customer
                  </el-button>
                </el-dropdown-item>
                <el-dropdown-item
                  v-permission="['delete-customers']"
                  v-if="scope.row.date_verified === null"
                  divided
                >
                  <el-button
                    icon="el-icon-delete"
                    @click="deleteCustomer(scope.index, scope.row.id, scope.row.business_name)"
                  >
                    Remove Customer
                  </el-button>
                </el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          </template>
        </v-client-table>
      </div>
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
        'action',
        'business_name',
        'customer_type.name',
        // 'area',
        'visits',
        'registrar.name',
        'assigned_officer',
        'address',
        // 'verifier.name',
        'created_at',
        // 'date_verified',
      ],

      options: {
        headings: {
          business_name: 'Name',
          'customer_type.name': 'Type',
          'visits': 'Last Visit',
          'registrar.name': 'Registered By',
          'assigned_officer': 'Field Staff',
          'verifier.name': 'Verified By',
        },
        rowAttributesCallback(row) {
          if (row.is_duplicate_entry === 1) {
            return { style: 'background: #ffec43f2; color: #000000' };
          }
          // return { style: 'background: #36c15ecf; color: #000000' };
        },
        pagination: {
          dropdown: true,
          chunk: 50,
        },
        perPage: 50,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['business_name', 'customer_type.name', 'created_at', 'date_verified'],
        filterable: ['business_name', 'customer_type.name', 'registrar.name'],
      },
      total: 0,
      loading: false,
      load_table: false,
      downloading: false,
      updatingCustomer: false,
      query: {
        page: 1,
        limit: 50,
        keyword: '',
        role: '',
        verify_type: 'all',
        rep_id: '',
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

      if (this.selectedCustomer.state_id !== null){
        this.selected_state = this.states.map(object => object.id).indexOf(this.selectedCustomer.state_id);
        this.setStateLGAs();
      }
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
    assignOfficer(staffId, customer_id) {
      const app = this;
      const customer_ids = [customer_id];
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
    verifyCustomer(index, id, business_name) {
      const app = this;
      const storeResource = new Resource('customers/verify');
      app.$confirm('Are you sure you want to verify ' + business_name + '?', 'Warning', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        app.load_table = true;
        storeResource.update(id)
          .then(() => {
            app.$message('Action Successful');
            // app.customer = response.customer;
            app.getList();
            app.load_table = false;
          });
      }).catch(() => {
        app.load_table = false;
      });
    },
    deleteCustomer(index, id, business_name) {
      const app = this;
      const storeResource = new Resource('customers/remove');
      app.$confirm('Are you sure you want to remove ' + business_name + '?', 'Warning', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        app.load_table = true;
        storeResource.destroy(id)
          .then(() => {
            app.$message('Customer Removed Successful');
            // app.customer = response.customer;
            app.list.splice(index - 1, 1);
            app.load_table = false;
          });
      }).catch(() => {
        app.load_table = false;
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
      if (confirm('You can only export the current view')) {
        this.export(this.list);
      }
      // fetch all data for export
    //   this.query.limit = this.total;
    //   this.downloading = true;
    //   customersResource.list(this.query)
    //     .then(response => {
    //       this.export(this.customers.data);

    //       this.downloading = false;
    //     });
    },
    export(export_data) {
      import('@/vendor/Export2Excel').then((excel) => {
        const tHeader = [
          'BUSINESS NAME',
          'TYPE',
          'ADDRESS',
          'REGISTERED BY',
          'FIELD STAFF',
          'DATE REGISTERED',
          'LAST VERIFIED DATE',
        ];
        const filterVal = [
          'business_name',
          'customer_type.name',
          'address',
          'registrar.name',
          'assigned_officer.name',
          'created_at',
          'date_verified',
        ];
        const data = this.formatJson(filterVal, export_data);
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'customer-list',
        });
        this.downloading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map((v) =>
        filterVal.map((j) => {
          if (j === 'customer_type.name') {
            if (v['customer_type'] !== null) {
              return v['customer_type']['name'];
            }
          }
          if (j === 'registrar.name') {
            if (v['registrar'] !== null) {
              return v['registrar']['name'];
            }
          }
          if (j === 'assigned_officer.name') {
            if (v['assigned_officer'] !== null) {
              return v['assigned_officer']['name'];
            }
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
