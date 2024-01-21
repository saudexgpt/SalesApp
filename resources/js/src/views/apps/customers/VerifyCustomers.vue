
<template>
  <el-card>
    <div slot="header" class="clearfix">
      <span>Verify Customers</span>
      <span class="pull-right">
        <el-button :loading="loadButton" circle type="danger" icon="el-icon-refresh" @click="loadCustomers(); loadProducts();" />
      </span>

    </div>
    <el-dialog
      :visible.sync="dialogVisible"
      title="Search Customers"
      width="90%">
      <v-client-table
        v-model="customersList"
        :columns="['action', 'id', 'business_name', 'code', 'area']"
        :options="options"
      >
        <template slot="action" slot-scope="props">
          <el-button
            round
            class="filter-item"
            type="danger"
            @click="sendCustomer(props.row)"
          >Select
          </el-button>
        </template>
      </v-client-table>
    </el-dialog>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th />
          <th>Customer</th>
          <th>Rep's Coordinate</th>
          <th>Verified Coordinate 1</th>
          <th>Verified Coordinate 2</th>
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
            <el-button
              round
              class="filter-item"
              type="primary"
              @click="selectCustomer(index)"
            >Select Customer
            </el-button><br>
            {{ customer_detail.business_name }}<br>
            {{ customer_detail.code }}
          </td>
          <td>
            <el-input
              v-model="customer_detail.coordinate_1"
              type="text"
              outline
              placeholder="Ex: 6.5365215,3.3658745"/>
          </td>
          <td>
            <el-input
              v-model="customer_detail.coordinate_2"
              type="text"
              outline
              placeholder="Ex: 6.5365215,3.3658745"/>
          </td>
          <td>
            <el-input
              v-model="customer_detail.coordinate_3"
              type="text"
              outline
              placeholder="Ex: 6.5365215,3.3658745"/>
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
              <vs-button color="success" type="filled" @click="submitCustomerVerification()">Submit</vs-button>
            </span>
          </th>
        </tr>
      </tfoot>
    </table>
  </el-card>
</template>

<script>
import { FormWizard, TabContent } from 'vue-form-wizard';
import 'vue-form-wizard/dist/vue-form-wizard.min.css';
import FilterOptions from '@/views/apps/reports/FilterOptions';
import Resource from '@/api/resource';
import checkPermission from '@/utils/permission';
export default {
  components: {
    FormWizard,
    TabContent,
    FilterOptions,
  },
  data() {
    return {
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate()); // one year from now
          return date > d;
        },
      },
      options: {
        headings: {
          action: '',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        filterByColumn: true,
        sortable: ['business_name', 'area'],
        filterable: ['business_name', 'area'],
      },
      customer_details: [],
      selectedRow: 0,
      loadForm: false,
      dialogVisible: false,
      loadButton: false,
      blockRemoval: false,
      fill_fields_error: false,
    };
  },
  computed: {
    customersList() {
      return this.$store.getters.customers;
    },
  },
  created() {
    this.addLine();
  },
  methods: {
    checkPermission,
    loadCustomers(){
      const app = this;
      app.loadButton = true;
      app.$store.dispatch('customer/fetch').then(() => {
        app.$message('Customers Reloaded');
        app.loadButton = false;
      });
    },
    selectCustomer(index){
      const app = this;
      app.selectedRow = index;
      app.dialogVisible = true;
    },
    isRowEmpty() {
      const checkEmptyLines = this.customer_details.filter(
        (detail) =>
          detail.customer_id === '');
      if (checkEmptyLines.length > 0) {
        return true;
      }
      return false;
    },
    addLine() {
      this.fill_fields_error = false;

      if (this.isRowEmpty()) {
        this.fill_fields_error = true;
        // this.invoice_items[index].seleted_category = true;
        return;
      } else {
        // if (this.invoice_items.length > 0)
        //     this.invoice_items[index].grade = '';
        this.customer_details.push({
          customer_id: '',
          business_name: '',
          code: '',
          coordinate_1: '',
          coordinate_2: '',
          coordinate_3: '',
        });
      }
    },
    removeLine(detailId) {
      this.fill_fields_error = false;
      if (!this.blockRemoval) {
        this.customer_details.splice(detailId, 1);
      }
    },
    sendCustomer(customer) {
      const app = this;
      const index = app.selectedRow;
      app.customer_details[index].customer_id = customer.id;
      app.customer_details[index].business_name = customer.business_name;
      app.customer_details[index].code = customer.code;
      app.customer_details[index].coordinate_1 = `${customer.latitude},${customer.longitude}`;
      app.customer_details[index].coordinate_2 = `${customer.latitude2},${customer.longitude2}`;
      app.customer_details[index].coordinate_3 = `${customer.latitude3},${customer.longitude3}`;
      app.dialogVisible = false;
    },
    submitCustomerVerification() {
      const app = this;
      const form = { customer_data: app.customer_details };
      this.$vs.loading();
      const createCustomer = new Resource('customers/update-coordinates');
      createCustomer
        .store(form)
        .then((response) => {
          app.$message({
            message: 'Coordinates updated Successfully!!!',
            type: 'success',
          });
          app.loadCustomers();
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

