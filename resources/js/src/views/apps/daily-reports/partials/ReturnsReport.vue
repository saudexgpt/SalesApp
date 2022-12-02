<template>
  <div v-loading="load" class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Action</th>
          <th>Rep</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Coordinates</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(rep_entry, index) in rep_entries" :key="index">
          <td>
            <div class="demo-alignment">
              <vs-button v-if="rep_entries.length > 1" radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeRepEntry(index)" />
              <vs-button v-if="index + 1 === rep_entries.length" radius color="primary" icon-pack="feather" icon="icon-plus" @click="addRepEntry(index)" />
            </div>
          </td>
          <td>
            <el-select v-model="rep_entry.rep_id" filterable style="width: 100%" @change="fetchCustomers($event, index)">
              <el-option
                v-for="(rep, rep_index) in reps"
                :key="rep_index"
                :label="rep.name"
                :value="rep.id"

              />
            </el-select>
          </td>
          <td>
            <el-select
              v-model="rep_entry.customer_details"
              value-key="id"
              placeholder="Select Customer"
              filterable
              style="width: 100%"
              @input="setCustomerDetails($event, index)"
            >
              <el-option
                v-for="(cust, cust_index) in customersList"
                :key="cust_index"
                :value="cust"
                :label="cust.business_name + ' ' + cust.address"
              >
                <span style="float: left"><strong>{{ cust.business_name }} [{{ cust.code }}]</strong></span>
                <span style="float: right; color: #8492a6; font-size: 12px">{{ cust.address }}</span>
              </el-option>
            </el-select>
            <!--Uncomment when you want to activate sales details-->
            <br><br>
            <el-button v-if="rep_entry.customer_id !== ''" round type="danger" icon="el-icon-goods" @click="setCustomerReturns(index, rep_entry)">Add Details</el-button>
          </td>
          <td>
            <el-date-picker
              v-model="rep_entry.entry_date"
              :picker-options="pickerOptions"
              type="date"
              placeholder="Transaction Date"
              style="width: 100%;"
              format="yyyy-MM-dd"
              value-format="yyyy-MM-dd"
            />
          </td>
          <td>
            <strong>Rep's</strong>
            <el-input
              v-model="rep_entry.rep_coordinate"
              placeholder="6.5270061,3.3766094"
            />
            <br>
            <strong>Manager's</strong>
            <el-input
              v-model="rep_entry.manager_coordinate"
              placeholder="6.5270161,3.3756094"
            />
          </td>
          <!-- <td>
            <input
              type="file"
              class="form-control"
              multiple
              @change="onImageChange($event, index)"
            ><br>
            <span v-for="(file, file_index) in uploadedFiles[index].files" :key="file_index">
              <img :src="`/storage/${file}`" width="50">
            </span>
          </td> -->
        </tr>
        <tr v-if="fill_rep_fields_error">
          <td colspan="5">
            <label
              class="label label-danger"
            >Please fill all empty fields before adding another row</label>
          </td>
        </tr>
        <tr v-if="!isRepRowEmpty()" >
          <td colspan="5">
            <el-button round type="success" @click="submitSalesReport()">Submit Report</el-button>
          </td>
        </tr>
      </tbody>
    </table>
    <el-dialog
      :visible.sync="dialogVisible"
      :title="'Product Returned by ' + customer_name"
      width="90%">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th/>
            <th>Product</th>
            <th>Quantity Return</th>
            <th>Expiry Date</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(each_return, index) in returned_items" :key="index">
            <td>
              <div class="demo-alignment">
                <vs-button v-if="returned_items.length > 1" radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeRow(index)" />
                <vs-button v-if="index + 1 === returned_items.length" radius color="success" icon-pack="feather" icon="icon-plus" @click="addRow(index)" />
              </div>
            </td>
            <td>
              <el-select
                v-model="each_return.product_index"
                placeholder="Select Product"
                filterable
                style="width: 100%"
                @input="fetchItemDetails(index)"
              >
                <el-option
                  v-for="(product, product_index) in products"
                  :key="product_index"
                  :value="product_index"
                  :label="product.name"
                />
              </el-select>
            </td>
            <!-- <td>{{ each_return.package_type }}</td> -->
            <td>
              <el-input
                v-model="each_return.quantity_returned"
                type="number"
                outline
                placeholder="Quantity Returned"
                min="1"
                @input="calculateTotal(index);"
              >
                <template slot="append">{{ each_return.package_type }}</template>
              </el-input>
            </td>
            <!-- <td>
              <el-input
                v-model="each_return.rate"
                type="number"
                outline
                @input="calculateTotal(index)"
              />
              <br>
              <small>{{ 'Amount: ₦' + each_return.amount.toLocaleString() }}</small>
            </td>
            <td>
              <el-input
                v-model="each_return.batch_no"
                type="text"
                style="width: 100%;"
                outline
              />
            </td> -->
            <td>
              <el-date-picker
                v-model="each_return.expiry_date"
                type="date"
                placeholder="Expiry Date"
                style="width: 100%;"
                format="yyyy/MM/dd"
                value-format="yyyy-MM-dd"
              />
            </td>
            <td>
              <el-input
                v-model="each_return.reason"
                type="textarea"
                placeholder="Reason for return"
                maxlength="70"
                show-word-limit
              />
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
      </table>
      <span slot="footer" class="dialog-footer">
        <el-button @click="clearForm()">Clear</el-button>
        <el-button type="danger" @click="cancelAction()">Cancel</el-button>
        <el-button :disabled="isRowEmpty()" type="primary" @click="addCustomerReturns()">Done</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
import Resource from '@/api/resource';
import { createUniqueString } from '@/utils/index';

export default {
  props: {
    teamId: {
      type: Number,
      default: () => null,
    },
    reps: {
      type: Array,
      default: () => [],
    },
    customersList: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate());
          return date >= d;
        },
      },
      rep_entries: [],
      activeName: '1',
      returned_items: [],
      customer_name: '',
      selected_index: '',
      total: 0,
      dialogVisible: false,
      fill_fields_error: false,
      fill_rep_fields_error: false,
      showSaveButton: true,
      extra_customers: '',
      // eslint-disable-next-line no-array-constructor
      uploadedFiles: [],
      products: [],
      load: false,
    };
  },
  created() {
    this.addRepEntry();
  },
  methods: {
    cancelAction(){
      for (let index = 0; index < this.returned_items.length; index++) {
        const detail = this.returned_items[index];
        if (detail.product_id === '' ||
          detail.quantity_returned === '' ||
          detail.rate === '' ||
          detail.expiry_date === '' ||
          // detail.batch_no === '' ||
          detail.reason === '') {
          this.removeRow(index);
        }
      }
      this.dialogVisible = false;
    },
    isRowEmpty() {
      const checkEmptyLines = this.returned_items.filter(
        (detail) =>
          detail.product_id === '' ||
          detail.quantity_returned === '' ||
          detail.rate === '' ||
          detail.expiry_date === '' ||
          // detail.batch_no === '' ||
          detail.reason === ''
      );
      if (checkEmptyLines.length > 0) {
        return true;
      }
      return false;
    },
    addRow() {
      this.fill_fields_error = false;
      if (this.isRowEmpty()) {
        this.fill_fields_error = true;
        // this.returned_items[index].seleted_category = true;
        return;
      } else {
        // if (this.returned_items.length > 0)
        //     this.returned_items[index].grade = '';
        this.returned_items.push({
          product_index: null,
          product_id: '',
          quantity_returned: 1,
          rate: 0,
          amount: 0,
          reason: '',
          expiry_date: '',
          batch_no: '',
        });
      }
    },
    removeRow(detailId) {
      this.fill_fields_error = false;
      if (!this.blockRemoval) {
        this.returned_items.splice(detailId, 1);
      }
    },
    clearForm() {
      this.returned_items = [];
      this.addRow();
      this.addCustomerReturns();
    },
    addCustomerReturns() {
      const app = this;
      const returned_items = app.returned_items;
      app.rep_entries[app.selected_index].returns = returned_items;
      // console.log(app.rep_entries[app.selected_index]);
      this.dialogVisible = false;
    },
    setCustomerReturns(index, customer) {
      this.selected_index = index;
      this.returned_items = [];
      if (!customer.returns) {
        this.addRow();
      } else {
        this.returned_items = customer.returns;
      }
      this.customer_name = customer.business_name;
      this.dialogVisible = true;
    },
    setCustomerDetails(customer, rowIndex){
      const app = this;
      app.rep_entries[rowIndex].customer_id = customer.id;
      app.rep_entries[rowIndex].business_name = customer.business_name;
    },
    isRepRowEmpty() {
      const checkEmptyLines = this.rep_entries.filter(
        (detail) =>
          detail.rep_id === '' ||
          detail.customer_id === '' ||
          detail.rep_coordinate === ''
      );
      if (checkEmptyLines.length > 0) {
        return true;
      }
      return false;
    },
    addRepEntry() {
      this.fill_rep_fields_error = false;

      if (this.isRepRowEmpty()) {
        this.fill_rep_fields_error = true;
        // this.returned_items[index].seleted_category = true;
        return;
      } else {
        // if (this.returned_items.length > 0)
        //     this.returned_items[index].grade = '';
        this.rep_entries.push({
          rep_id: '',
          customer_details: {},
          business_name: '',
          customer_id: '',
          customersList: [],
          entry_date: new Date(),
          unique_sales_id: createUniqueString(),
          rep_coordinate: '',
          manager_coordinate: '',
        });
      }
    },
    removeRepEntry(detailId) {
      this.fill_rep_fields_error = false;
      if (!this.repBlockRemoval) {
        this.rep_entries.splice(detailId, 1);
      }
    },
    fetchItemDetails(index) {
      const app = this;
      const product_index = app.returned_items[index].product_index;
      const item = app.products[product_index];
      app.returned_items[index].product_id = item.id;
      app.returned_items[index].rate = item.price.sale_price;
      app.returned_items[index].package_type = item.package_type;
      app.calculateTotal(index);
    },
    calculateTotal(index) {
      const app = this;
      // Get total amount for this item without tax
      if (index !== null) {
        const quantity = app.returned_items[index].quantity_returned;
        const unit_rate = app.returned_items[index].rate;
        // const main_unit_rate = app.returned_items[index].main_rate;
        app.returned_items[index].amount = parseFloat(
          quantity * unit_rate,
        ).toFixed(2);
      }
    },
    fetchCustomers(rep_id, index) {
      const app = this;
      //   // if (!app.hideCustomersList) {
      //   const customerResource = new Resource('customers/rep-customers');
      const param = { rep_id, team_id: app.teamId };
      app.fetchRepProducts(param);
    //   customerResource.list(param)
    //     .then(response => {
    //       // app.customers = response.customers;
    //       app.rep_entries[index].customersList = response.customers;
    //     });
    //   // }
    },
    fetchRepProducts(param) {
      const app = this;
      const getProducts = new Resource('products/rep-products');
      getProducts.list(param).then((response) => {
        app.products = response.team_products;
      });
    },
    // onImageChange(e, index) {
    //   const app = this;
    //   const files = e.target.files;
    //   let filesToBeUploaded = '';
    //   for (let i = 0; i < files.length; i++) {
    //     filesToBeUploaded = e.target.files[i];
    //     // console.log(file);
    //     // const filesToBeUploaded = file[0];
    //     app.submitUpload(filesToBeUploaded, index);
    //   }
    //   // app.rep_entries[index].files = filesToBeUploaded;
    // },
    // submitUpload(filesToBeUploaded, index) {
    //   const app = this;
    //   app.loading = true;
    //   const formData = new FormData();
    //   formData.append('files', filesToBeUploaded);
    //   formData.append('type', 'sales');
    //   const updatePhotoResource = new Resource('attach/files');
    //   updatePhotoResource.store(formData)
    //     .then(response => {
    //       app.rep_entries[index].files.push(response);
    //       app.uploadedFiles[index].files.push(response);
    //     })
    //     .catch(e => {
    //       console.log(e);
    //     });
    // },
    submitSalesReport() {
      const app = this;
      app.$confirm('Are you sure you want to submit these entries?', 'Warning', {
        confirmButtonText: 'Yes Submit',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        this.$message({
          type: 'info',
          message: 'Sending...',
        });
        app.load = true;
        if (app.rep_entries.length > 0) {
          const formattedEntries = [];
          app.rep_entries.forEach(entry => {
            formattedEntries.push({
              rep_id: entry.rep_id,
              customer_id: entry.customer_id,
              entry_date: entry.entry_date,
              unique_sales_id: entry.unique_sales_id,
              rep_coordinate: entry.rep_coordinate,
              manager_coordinate: entry.manager_coordinate,
              returns: entry.returns,
              // main_amount: entry.main_amount,
            });
          });
          const unsaved_returns = { unsaved_returns: formattedEntries };
          const storeResource = new Resource('returns/store');
          storeResource.store(unsaved_returns).then(() => {
            this.$message({
              type: 'success',
              message: 'Sales Entries Submitted',
            });
            app.rep_entries = [];
            app.addRepEntry();
            app.load = false;
          }).catch(() => {
            this.$message({
              type: 'danger',
              message: 'An error Occured',
            });
            app.load = false;
          });
        }
        // app.loadForm = false;
      }).catch(() => {
        app.load = false;
      });
    },
  },
};
</script>
