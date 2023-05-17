<template>
  <div v-loading="load" class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Action</th>
          <th>Rep</th>
          <th>Customer</th>
          <!-- <th>Opening Debt</th> -->
          <th>Total Sales (NGN)</th>
          <th>Date</th>
          <!-- <th>Coordinates</th> -->
          <!-- <th>Attach File</th> -->
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
            <el-select v-model="rep_entry.rep_id" filterable style="width: 100%" @change="fetchCustomers($event, index); fetchInvoiceBooklets($event, index);">
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
            <!--Uncomment when you want to activate sales details-->
          </td>
          <!-- <td>0</td> -->
          <td>
            <!-- {{ customer.amount }} -->

            <el-button v-if="rep_entry.customer_id !== ''" round type="danger" icon="el-icon-goods" @click="setCustomerSales(index, rep_entry)">Add Sales Details</el-button>
            <br><br>

            <el-input
              v-model="rep_entry.amount"
              placeholder="Enter Amount"
              type="number"
            />
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
          <!-- <td>
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
          </td> -->
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
            <el-button round type="success" @click="submitSalesReport()">Submit Sales Report</el-button>
          </td>
        </tr>
      </tbody>
    </table>
    <el-dialog
      :visible.sync="dialogVisible"
      :title="'Sales for ' + customer_name"
      width="90%">
      <el-row :gutter="10">
        <el-col :span="12">
          <el-select v-model="selected_booklet" value-key="id" filterable style="width: 100%" placeholder="Select Booklet Range" @input="fetchUnusedInvoices()">
            <el-option v-for="(booklet, booklet_index) in invoice_booklets" :key="booklet_index" :value="booklet" :label="`${booklet.lower_limit} - ${booklet.upper_limit}`"/>
          </el-select>
        </el-col>
        <el-col :span="12">
          <el-select v-model="selected_invoice_no" filterable style="width: 100%" placeholder="Select Invoice No." @input="setInvoiceNo()">
            <el-option v-for="(invoice, invoice_index) in unused_invoice_nos" :key="invoice_index" :value="invoice" :label="invoice"/>
          </el-select>
        </el-col>
      </el-row>
      <table v-if="selected_invoice_no !== ''" class="table table-bordered">
        <thead>
          <tr>
            <th/>
            <th>Product</th>
            <!-- <th>Package Type</th> -->
            <th>Quantity Sold</th>
            <th>Rate (NGN)</th>
            <th>Total (NGN)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(sale, index) in invoice_items" :key="index">
            <td>
              <div class="demo-alignment">
                <vs-button v-if="invoice_items.length > 1" radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeLine(index)" />
                <vs-button v-if="index + 1 === invoice_items.length" radius color="success" icon-pack="feather" icon="icon-plus" @click="addLine(index)" />
              </div>
            </td>
            <td v-loading="loadProduct">
              <el-select
                v-model="sale.item"
                value-key="id"
                placeholder="Select Product"
                filterable
                style="width: 100%"
                @input="fetchItemDetails(index, $event)"
              >
                <el-option
                  v-for="(product, item_index) in products"
                  :key="item_index"
                  :value="product"
                  :label="product.name"
                />
              </el-select>
            </td>
            <td>
              <el-input
                v-model="sale.quantity"
                type="number"
                outline
                placeholder="Quantity"
                min="1"
                @input="calculateTotal(index);"
              >
                <template slot="append">{{ sale.type }}</template>
              </el-input>
            </td>
            <td>
              <el-input
                v-model="sale.rate"
                type="number"
                outline
                @input="calculateTotal(index)"
              />
            </td>
            <td>{{ sale.amount }}</td>
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
        <el-button type="danger" @click="clearForm()">Cancel</el-button>
        <!-- <el-button type="danger" @click="cancelAction()">Cancel</el-button> -->
        <el-button :disabled="isRowEmpty()" type="primary" @click="addCustomerSales()">Done</el-button>
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
      rep_entries: [],
      activeName: '1',
      invoice_items: [],
      invoice_booklets: [],
      unused_invoice_nos: [],
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
      // products: [],
      load: false,
      loadProduct: false,
      rowIndex: '',
      selected_invoice_no: '',
      selected_booklet: '',
    };
  },
  computed: {
    products() {
      return this.$store.getters.allProducts;
    },
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
    cancelAction(){
      for (let index = 0; index < this.invoice_items.length; index++) {
        const detail = this.invoice_items[index];
        if (detail.item_id === '' || detail.quantity === '' || detail.quantity === 0 || detail.rate === '') {
          this.removeLine(index);
        }
      }
      this.dialogVisible = false;
    },
    isRowEmpty() {
      const checkEmptyLines = this.invoice_items.filter(
        (detail) =>
          detail.item_id === '' ||
          detail.quantity === '' ||
          detail.quantity === 0 ||
          detail.rate === ''
      );
      if (checkEmptyLines.length > 0) {
        return true;
      }
      return false;
    },
    setCustomerSales(index, customer) {
      const app = this;

      app.selected_index = index;
      app.invoice_booklets = app.rep_entries[index].invoice_booklets;
      // const { rep_id } = customer;
      // const param = { rep_id, team_id: app.teamId };
      // app.fetchRepProducts(param);
      app.invoice_items = [];
      if (!customer.invoice_items) {
        this.addLine();
      } else {
        this.invoice_items = customer.invoice_items;
      }
      this.customer_name = customer.business_name;
      this.dialogVisible = true;
    },
    setInvoiceNo() {
      const app = this;
      app.rep_entries[app.selected_index].invoice_no = app.selected_invoice_no;
    },
    fetchUnusedInvoices() {
      const app = this;
      app.rep_entries[app.selected_index].booklet_id = app.selected_booklet.id;
      app.unused_invoice_nos = app.selected_booklet.unused_invoice_numbers.split(',');
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
          detail.amount === ''
          // detail.rep_coordinate === ''
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
        // this.invoice_items[index].seleted_category = true;
        return;
      } else {
        // if (this.invoice_items.length > 0)
        //     this.invoice_items[index].grade = '';
        this.rep_entries.push({
          rep_id: '',
          booklet_id: '',
          invoice_no: '',
          customer_details: {},
          business_name: '',
          customer_id: '',
          customersList: [],
          invoice_booklets: [],
          entry_date: new Date(),
          unique_sales_id: createUniqueString(),
          amount: '',
          main_amount: '',
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
    addCustomerSales() {
      const app = this;
      const invoice_items = app.invoice_items;
      var total_sales = 0;
      var original_total_amount = 0;
      for (let count = 0; count < invoice_items.length; count++) {
        // const tax_rate = app.invoice_items[count].tax;
        // const quantity = app.invoice_items[count].quantity;
        // const unit_rate = app.invoice_items[count].rate;
        // total_tax += parseFloat(tax_rate * quantity * unit_rate);
        total_sales += parseFloat(app.invoice_items[count].amount);
        original_total_amount += parseFloat(app.invoice_items[count].main_amount);
      }
      app.rep_entries[app.selected_index].amount = total_sales;
      app.rep_entries[app.selected_index].main_amount = original_total_amount;
      app.rep_entries[app.selected_index].invoice_items = invoice_items;
      // console.log(app.rep_entries[app.selected_index]);
      this.dialogVisible = false;
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
        this.invoice_items.push({
          item_index: null,
          item_id: '',
          quantity: '',
          rate: '',
          delivery_mode: 'now',
          quantity_supplied: 0,
          amount: 0,
          batch_no: 0,
          expiry_date: 0,
        });
      }
    },
    removeLine(detailId) {
      this.fill_fields_error = false;
      if (!this.blockRemoval) {
        this.invoice_items.splice(detailId, 1);
        this.calculateTotal(null);
      }
    },
    clearForm() {
      this.invoice_items = [];
      this.addLine();
      this.addCustomerSales();
    },
    fetchItemDetails(index, product) {
      const app = this;
      const item = product;
      app.invoice_items[index].main_rate = item.price.sale_price;
      app.invoice_items[index].rate = item.price.sale_price;
      app.invoice_items[index].item_id = item.id;
      app.invoice_items[index].type = (item.basic_unit) ? item.basic_unit : item.package_type;
      app.invoice_items[index].quantity_per_carton = item.quantity_per_carton;
      app.invoice_items[index].no_of_cartons = 0;
      app.calculateTotal(index);
    },
    // showItemsInStock(index) {
    //   const app = this;
    //   app.batches_of_items_in_stock =
    //     app.invoice_items[index].batches_of_items_in_stock;
    //   app.items_in_stock_dialog = true;
    // },
    // calculateNoOfCartons(index) {
    //   const app = this;
    //   if (index !== null) {
    //     const quantity = app.invoice_items[index].quantity;
    //     const quantity_per_carton =
    //       app.invoice_items[index].quantity_per_carton;
    //     if (quantity_per_carton > 0) {
    //       const no_of_cartons = quantity / quantity_per_carton;
    //       app.invoice_items[index].no_of_cartons = no_of_cartons; // + parseFloat(tax);
    //     }
    //   }
    // },
    calculateTotal(index) {
      const app = this;
      // Get total amount for this item without tax
      if (index !== null) {
        const quantity = Math.abs(app.invoice_items[index].quantity); // we want only positive numbers
        const unit_rate = app.invoice_items[index].rate;
        const main_unit_rate = app.invoice_items[index].main_rate;
        app.invoice_items[index].amount = parseFloat(
          quantity * unit_rate,
        ).toFixed(2);
        app.invoice_items[index].main_amount = parseFloat(
          quantity * main_unit_rate,
        ).toFixed(2);// + parseFloat(tax);
        // app.invoice_items[index].quantity_supplied = quantity;
      }

      // we now calculate the running total of items invoiceed for with tax //////////
      // let total_tax = 0;
    //   let subtotal = 0;
    //   for (let count = 0; count < app.invoice_items.length; count++) {
    //     // const tax_rate = app.invoice_items[count].tax;
    //     // const quantity = app.invoice_items[count].quantity;
    //     // const unit_rate = app.invoice_items[count].rate;
    //     // total_tax += parseFloat(tax_rate * quantity * unit_rate);
    //     subtotal += parseFloat(app.invoice_items[count].amount);
    //   }
    //   // app.form.tax = total_tax.toFixed(2);
    //   app.form.subtotal = subtotal.toFixed(2);
    //   app.form.discount = parseFloat(
    //     (app.discount_rate / 100) * subtotal,
    //   ).toFixed(2);
    //   // subtract discount
    //   app.form.amount = parseFloat(subtotal - app.form.discount).toFixed(2);
    },
    requestCustomer(index) {
      const app = this;
      app.rowIndex = index;
      app.$emit('selectCustomer', 'sales');
    },
    fetchCustomers(rep_id, index) {
    //   const app = this;
    //   // if (!app.hideCustomersList) {
    //   const customerResource = new Resource('customers/rep-customers');
    //   const param = { rep_id, team_id: app.teamId };
    //   app.fetchRepProducts(param);
    //   customerResource.list(param)
    //     .then(response => {
    //       // app.customers = response.customers;
    //       app.rep_entries[index].customersList = response.customers;
    //     });
    //   // }
    },
    fetchInvoiceBooklets(rep_id, index) {
      const app = this;
      // if (!app.hideCustomersList) {
      const customerResource = new Resource('invoice-booklets/fetch-rep');
      const param = { rep_id };
      customerResource.list(param)
        .then(response => {
          app.rep_entries[index].invoice_booklets = response.invoice_booklets;
        });
      // }
    },
    fetchRepProducts(param) {
      const app = this;
      app.loadProduct = true;
      const getProducts = new Resource('products/rep-products');
      getProducts.list(param).then((response) => {
        app.products = response.rep_products;
        app.all_products = response.team_products;
        app.loadProduct = false;
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
      app.$confirm('Are you sure you want to submit these sales entries?', 'Warning', {
        confirmButtonText: 'Yes Submit',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        // this.$message({
        //   type: 'info',
        //   message: 'Sending...',
        // });
        app.load = true;
        if (app.rep_entries.length > 0) {
          const formattedEntries = [];
          app.rep_entries.forEach(entry => {
            formattedEntries.push({
              rep_id: entry.rep_id,
              customer_id: entry.customer_id,
              entry_date: entry.entry_date,
              unique_sales_id: createUniqueString(),
              amount: entry.amount,
              rep_coordinate: '6.547240860332957,3.3654287095494184', // entry.rep_coordinate,
              manager_coordinate: entry.manager_coordinate,
              invoice_items: entry.invoice_items,
              main_amount: entry.main_amount,
              booklet_id: entry.booklet_id,
              invoice_no: entry.invoice_no,
            });
          });
          const unsaved_orders = { unsaved_orders: formattedEntries };
          const submitSales = new Resource('sales/store');
          submitSales.store(unsaved_orders).then(() => {
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
