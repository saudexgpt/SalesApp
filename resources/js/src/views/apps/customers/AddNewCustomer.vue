<template>
  <div>
    <vx-card v-loading="load_table">
      <div class="vx-row">
        <div class="vx-col lg:w-4/5 w-full">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
            <span class="font-medium text-lg">Add New Customer</span>
          </div>
          <vs-divider />
        </div>
        <!-- <div class="vx-col lg:w-1/5 w-full">
          <div class="flex items-end px-3">
            <span class="vx-col flex-1">
              <router-link
                :to="'/inventory/view'"
              >
                <el-button
                  round
                  class="filter-item"
                  type="primary"
                  icon="el-icon-s-goods"
                >View Inventory
                </el-button>
              </router-link>
            </span>
          </div>
        </div> -->
      </div>
      <!-- <filter-options :hide-customers-list="true" :submit-on-rep-change="true" panel="none" @submitQuery="setFormParams" /><br><br> -->
      <table class="table table-bordered">
        <thead>
          <tr>
            <th />
            <th>Name</th>
            <th>Coordinate</th>
            <!-- <th>Code</th> -->
            <th>Type</th>
            <th>Address</th>
            <th>Area</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(customer_detail, index) in customer_details" :key="index">
            <td>
              <span>
                <vs-button v-if="customer_details.length > 1" radius color="danger" type="filled" icon-pack="feather" icon="icon-trash-2" @click="removeLine(index)"/>
                <vs-button v-if="index + 1 === customer_details.length" radius color="success" type="filled" icon-pack="feather" icon="icon-plus" @click="addLine(index)"/>
              </span>
            </td>
            <td>
              <el-input
                v-model="customer_detail.business_name"
                type="text"
                outline
                placeholder="Customer Name"/>
            </td>
            <td>
              <el-input
                v-model="customer_detail.coordinate"
                type="text"
                outline
                placeholder="Ex: 6.5365215,3.3658745"/>
            </td>
            <!-- <td>
              <el-input
                v-model="customer_detail.code"
                type="text"
                outline
                placeholder="Ex: BEL-000001"/>
            </td> -->
            <td>
              <el-select
                v-model="customer_detail.customer_type_id"
                placeholder="Select Customer Type"
                filterable
                class="span"
                style="width: 100%"
              >
                <el-option
                  v-for="(type, type_index) in customer_types"
                  :key="type_index"
                  :value="type.id"
                  :label="type.name"
                />
              </el-select>
            </td>
            <td>
              <el-input
                v-model="customer_detail.address"
                type="text"
                outline
                placeholder="Enter Address"/>
            </td>
            <td>
              <el-input
                v-model="customer_detail.area"
                type="text"
                outline
                placeholder="Enter Area"/>
            </td>
          </tr>
          <tr v-if="fill_fields_error">
            <td colspan="4">
              <label
                class="label label-danger"
              >Please fill all empty fields before adding another row</label>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="7">
              <span class="pull-right">
                <vs-button color="success" type="filled" @click="submitCustomer()">Submit</vs-button>
              </span>
            </th>
          </tr>
        </tfoot>
      </table>
    </vx-card>
  </div>
</template>

<script>
import Resource from '@/api/resource';
import permission from '@/directive/permission'; // Permission directive
import checkPermission from '@/utils/permission'; // Permission checking
import FilterOptions from '@/views/apps/reports/FilterOptions';
export default {
  name: 'Customers',
  components: { FilterOptions },
  directives: { permission },
  data() {
    return {
      sales_reps: [],
      products: [],
      customer_details: [],
      customer_types: [],
      fill_fields_error: false,
      // rep_id: '',
      load_table: false,
    };
  },
  created() {
    this.fetchCustomerTypes();
    this.addLine();
  },
  methods: {
    checkPermission,
    fetchCustomerTypes() {
      const customerTypesResource = new Resource('customer-types/fetch');
      customerTypesResource
        .list()
        .then((response) => {
          this.customer_types = response.customer_types;
        })
        .catch((error) => {
          console.log(error);
          this.load_table = false;
        });
    },
    setFormParams(param){
      const app = this;
      app.rep_id = param.rep_id;
    },
    addLine(index) {
      this.fill_fields_error = false;

      const checkEmptyLines = this.customer_details.filter(
        (detail) =>
          detail.business_name === '' ||
          // detail.code === '' ||
          detail.customer_type_id === '' ||
          detail.coordinate === '' ||
          detail.address === '' ||
          detail.area === ''
      );

      if (checkEmptyLines.length >= 1 && this.customer_details.length > 0) {
        this.fill_fields_error = true;
        // this.customer_details[index].seleted_category = true;
        return;
      } else {
        // if (this.customer_details.length > 0)
        //     this.customer_details[index].grade = '';

        this.customer_details.push({
          item_index: null,
          business_name: '',
          // code: '',
          customer_type_id: '',
          coordinate: '',
          address: '',
          area: '',
          rep_id: '',
        });
      }
    },
    removeLine(detailId) {
      this.fill_fields_error = false;
      if (!this.blockRemoval) {
        this.customer_details.splice(detailId, 1);
      }
    },
    submitCustomer() {
      const app = this;
      //   app.customer_details.forEach(customer_detail => {
      //     customer_detail.relating_officer = app.rep_id;
      //   });
      var form = { unsaved_customers: app.customer_details };
      this.$vs.loading();
      const createCustomer = new Resource('customers/store');
      createCustomer
        .store(form)
        .then((response) => {
          app.$message({
            message: 'Customer Details Created Successfully!!!',
            type: 'success',
          });
          app.customer_details = [];
          app.addLine();
          this.$vs.loading.close();
        })
        .catch((error) => {
          this.$vs.loading.close();
          console.log(error.message);
        });
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
