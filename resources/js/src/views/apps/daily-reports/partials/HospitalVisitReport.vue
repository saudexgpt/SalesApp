<template>
  <div v-loading="load" class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Action</th>
          <th>Rep</th>
          <th>Customer</th>
          <th>Purpose</th>
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
            <el-button
              v-if="rep_entry.customer_id === ''"
              round
              class="filter-item"
              type="primary"
              @click="requestCustomer(index)"
            >Select Customer
            </el-button>
            <el-button
              v-else
              round
              class="filter-item"
              type="warning"
              @click="requestCustomer(index)"
            >Change Customer
            </el-button>
            <br>
            {{ rep_entry.business_name }}
            <!-- <el-select
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
            </el-select> -->
          </td>
          <!-- <td>0</td> -->
          <td>
            <!-- {{ customer.amount }} -->
            <el-select
              v-model="rep_entry.purpose"
              style="width: 100%"
            >
              <el-option value="Detailing" label="Detailing" />
              <el-option value="Clinical Meeting" label="Clinical Meeting" />
            </el-select>
            <!--Uncomment when you want to activate sales details-->
            <br><br>
            <el-button v-if="rep_entry.customer_id !== ''" round type="danger" icon="el-icon-goods" @click="setCustomerVisitDetails(index, rep_entry)">Add Details</el-button>
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
          <td colspan="6">
            <label
              class="label label-danger"
            >Please fill all empty fields before adding another row</label>
          </td>
        </tr>
        <tr v-if="!isRepRowEmpty()" >
          <td colspan="6">
            <el-button round type="success" @click="submitVisitsReport()">Submit Report</el-button>
          </td>
        </tr>
      </tbody>
    </table>
    <el-dialog
      :visible.sync="dialogVisible"
      :title="'Visit Details for ' + customer_name + customer_id"
      width="90%">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th/>
            <th>Personnel Contacted</th>
            <th>Select Products</th>
            <!-- <th>Follow-up Schedule</th> -->
            <th>Feedback/Comment</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(detail, index) in detailed_products" :key="index">
            <td>
              <div class="demo-alignment">
                <vs-button v-if="detailed_products.length > 1" radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeLine(index)" />
                <vs-button v-if="index + 1 === detailed_products.length" radius color="success" icon-pack="feather" icon="icon-plus" @click="addLine(index)" />
              </div>
            </td>
            <td>
              <!-- <el-input
                v-model="detail.contacted_personnel"
                type="textarea"
                autosize
                placeholder="Name & Phone No."/> -->
              <el-select
                v-model="detail.contacted_personnel"
                placeholder="Select Contact"
                filterable
                style="width: 100%"
                collapse-tags
              >
                <el-option
                  v-for="(contact, item_index) in contacts"
                  :key="item_index"
                  :value="contact.name + ' (' + contact.phone1 + ')'"
                  :label="contact.name + ' (' + contact.phone1 + ')'"
                />
              </el-select>
              <el-popover
                placement="right"
                width="400"
                trigger="click">
                <div>

                  <el-input
                    v-model="form.name"
                    autosize
                    placeholder="Contact Name"/>
                  <br><br>
                  <el-input
                    v-model="form.phone1"
                    autosize
                    placeholder="Phone No."/>
                  <br><br>
                  <el-button type="primary" @click="saveCustomerContact(index)">Save</el-button>
                </div>
                <el-button slot="reference" :id="'add_'+index" type="warning">Add New</el-button>
              </el-popover>
            </td>
            <td>
              <el-select
                v-model="detail.products"
                placeholder="Select Product"
                filterable
                collapse-tags
                style="width: 100%"
                multiple
              >
                <el-option
                  v-for="(product, item_index) in products"
                  :key="item_index"
                  :value="product.name"
                  :label="product.name"
                />
              </el-select>
            </td>
            <!-- <td>
              <el-date-picker
                v-model="detail.hospital_follow_up_schedule"
                :picker-options="pickerOptions"
                type="datetime"
                default-time="09:00:00"
                placeholder="Schedule Date"
                style="width: 100%;"
              />
            </td> -->
            <td>
              <el-input
                v-model="detail.comments"
                type="textarea"
                maxlength="160"
                show-word-limit
                placeholder="Give feedback"/>
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
        <el-button @click="cancelAction()">Cancel</el-button>
        <el-button :disabled="isRowEmpty()" type="primary" @click="addVisitDetails()">Done</el-button>
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
    selectedCustomer: {
      type: Object,
      default: () => null,
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
      form: {
        name: '',
        phone1: '',
        phone2: '',
        role: '',
        dob: '',
        gender: '',
      },
      rep_entries: [],
      activeName: '1',
      detailed_products: [],
      customer_name: '',
      customer_id: '',
      contacts: [],
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
      rowIndex: '',
    };
  },
  watch: {
    selectedCustomer(){
      // console.log('Value Changed');
      const app = this;
      const customer = app.selectedCustomer;
      const rowIndex = app.rowIndex;
      this.setCustomerDetails(customer, rowIndex);
    },
  },
  created() {
    this.addRepEntry();
  },
  methods: {
    requestCustomer(index) {
      const app = this;
      app.rowIndex = index;
      app.$emit('selectCustomer', 'detailing');
    },
    cancelAction(){
      for (let index = 0; index < this.detailed_products.length; index++) {
        const detail = this.detailed_products[index];
        if (detail.item_id === '' || detail.quantity === '' || detail.quantity === 0 || detail.rate === '') {
          this.removeLine(index);
        }
      }
      this.dialogVisible = false;
    },
    setCustomerVisitDetails(index, customer) {
      this.selected_index = index;
      this.detailed_products = [];
      if (!customer.detailed_products) {
        this.addLine();
      } else {
        this.detailed_products = customer.detailed_products;
      }
      this.customer_name = customer.business_name;
      this.customer_id = customer.customer_id;
      this.contacts = customer.contacts;
      this.dialogVisible = true;
    },
    setCustomerDetails(customer, rowIndex){
      const app = this;
      app.rep_entries[rowIndex].customer_id = customer.id;
      app.rep_entries[rowIndex].business_name = customer.business_name;
      app.rep_entries[rowIndex].contacts = customer.customer_contacts;
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
        // this.detailed_products[index].seleted_category = true;
        return;
      } else {
        // if (this.detailed_products.length > 0)
        //     this.detailed_products[index].grade = '';
        this.rep_entries.push({
          rep_id: '',
          contacts: [],
          business_name: '',
          customer_id: '',
          customersList: [],
          entry_date: new Date(),
          unique_visits_id: createUniqueString(),
          purpose: 'Detailing',
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
    addVisitDetails() {
      const app = this;
      const detailed_products = app.detailed_products;
      app.rep_entries[app.selected_index].detailed_products = detailed_products;
      // console.log(app.rep_entries[app.selected_index]);
      this.dialogVisible = false;
    },
    isRowEmpty() {
      const checkEmptyLines = this.detailed_products.filter(
        (detail) =>
          detail.hospital_contacts === '' ||
          detail.comments === '' ||
          detail.products.length < 1
      );
      if (checkEmptyLines.length > 0) {
        return true;
      }
      return false;
    },
    addLine() {
      this.fill_fields_error = false;
      if (this.isRowEmpty()) {
        this.fill_fields_error = true;
        // this.detailed_products[index].seleted_category = true;
        return;
      } else {
        // if (this.detailed_products.length > 0)
        //     this.detailed_products[index].grade = '';
        this.detailed_products.push({
          item_index: null,
          products: [],
          // hospital_follow_up_schedule: '',
          contacted_personnel: '',
          comments: '',
        });
      }
    },
    removeLine(detailId) {
      this.fill_fields_error = false;
      if (!this.blockRemoval) {
        this.detailed_products.splice(detailId, 1);
      }
    },
    clearForm() {
      const detailed_products = this.detailed_products;
      for (let index = 0; index < detailed_products.length; index++) {
        this.products[index].total_balance = parseInt(this.products[index].initial_total_balance);
      }
      this.detailed_products = [];
      this.addLine();
      this.addVisitDetails();
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
    submitVisitsReport() {
      const app = this;
      app.$confirm('Are you sure you want to submit these visit entries?', 'Warning', {
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
              unique_visits_id: entry.unique_visits_id,
              amount: entry.amount,
              rep_coordinate: entry.rep_coordinate,
              manager_coordinate: entry.manager_coordinate,
              detailed_products: entry.detailed_products,
              purpose: entry.purpose,
            });
          });
          const unsaved_visits = { unsaved_visits: formattedEntries };
          const storeResource = new Resource('visits/store');
          storeResource.store(unsaved_visits).then(() => {
            this.$message({
              type: 'success',
              message: 'Visit details Submitted',
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
    saveCustomerContact(index) {
      const app = this;
      console.log(app.customer_id);
      const form = [app.form]; // we need this in an array form
      const param = { customer_id: app.customer_id, customer_contacts: form };
      const storeResource = new Resource('customers/add-customer-contact');
      storeResource.store(param).then((response) => {
        app.form = {
          name: '',
          phone1: '',
          phone2: '',
          role: '',
          dob: '',
          gender: '',
        };
        app.contacts = response;
        app.detailed_products[index].contacted_personnel = response[0].name + ' (' + response[0].phone1 + ')';
        document.getElementById('add_' + index).click();
      }).catch(() => {
        this.$message({
          type: 'danger',
          message: 'An error Occured',
        });
        app.load = false;
      });
    },
  },
};
</script>
