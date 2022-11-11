<template>
  <div class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Action</th>
          <th>Customer Name</th>
          <!-- <th>Opening Debt</th> -->
          <!-- <th>Total Returns (NGN)</th>
          <th>Payment Due Date</th> -->
        </tr>
      </thead>
      <tbody>
        <tr v-for="(customer, index) in customersReturnsList" :key="index">
          <td>
            <el-tooltip :content="'Add Products Returned from ' + customer.business_name" class="item" effect="dark" placement="top-start">
              <el-button circle type="primary" icon="el-icon-goods" @click="setCustomerReturns(index, customer)" />
            </el-tooltip>
          </td>
          <td>
            {{ customer.business_name }}
            <el-button v-if="customer.can_delete === 'yes'" circle type="danger" icon="el-icon-delete" @click="removeExtraCustomer(customer.id)" />
          </td>
          <!-- <td>0</td> -->
          <!-- <td>{{ customer.amount }}</td>
          <td>
            <el-date-picker
              v-model="customer.due_date"
              :picker-options="pickerOptions"
              type="date"
              placeholder="Due Date"
              style="width: 100%;"
              format="yyyy/MM/dd"
              value-format="yyyy-MM-dd"
            />
          </td> -->
        </tr>
        <tr>
          <td colspan="4">
            <el-select
              v-model="extra_customers"
              placeholder="Select Customer"
              filterable
              style="width: 100%"
              @input="addExtraCustomers($event)"
            >
              <el-option
                v-for="(customer, product_index) in myCustomers"
                :key="product_index"
                :value="product_index"
                :label="customer.business_name + ' ' + customer.address"
              >
                <span style="float: left"><strong>{{ customer.business_name }}</strong></span>
                <span style="float: right; color: #8492a6; font-size: 12px">{{ customer.address }}</span>
              </el-option>
            </el-select>
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
            <!-- <th>Package Type</th> -->
            <th>Quantity Return</th>
            <!-- <th>Rate</th>
            <th>Batch No.</th> -->
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
// import Resource from '@/api/resource';
export default {
  props: {
    customersReturnsList: {
      type: Array,
      default: () => [],
    },
    myCustomers: {
      type: Array,
      default: () => [],
    },
    products: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
    //   pickerOptions: {
    //     disabledDate(date) {
    //       var d = new Date(); // today
    //       d.setDate(d.getDate() - 1);
    //       return date < d;
    //     },
    //   },
      activeName: '1',
      returned_items: [],
      customer_name: '',
      selected_index: '',
      total: 0,
      dialogVisible: false,
      fill_fields_error: false,
      showSaveButton: true,
      extra_customers: '',
    };
  },
  methods: {
    fetchItemDetails(index) {
      const app = this;
      const product_index = app.returned_items[index].product_index;
      const item = app.products[product_index];
      app.returned_items[index].product_id = item.id;
      app.returned_items[index].rate = item.price.sale_price;
      app.returned_items[index].package_type = item.package_type;
      app.calculateTotal(index);
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
    addCustomerReturns() {
      const app = this;
      const returned_items = app.returned_items;
      app.customersReturnsList[app.selected_index].returns = returned_items;
      // console.log(app.customersReturnsList[app.selected_index]);
      this.dialogVisible = false;
    },
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
    addExtraCustomers(value) {
      const app = this;
      const customer_id = app.myCustomers[value].id;
      if (!app.customersReturnsList.filter(e => e.id === customer_id).length > 0) {
        app.myCustomers[value].customer_id = customer_id;
        // app.myCustomers[value].payment_mode = 'later';
        app.myCustomers[value].can_delete = 'yes';
        app.customersReturnsList.push(app.myCustomers[value]);
      }
    },
    removeExtraCustomer(customer_id) {
      const app = this;
      for (let count = 0; count < app.customersReturnsList.length; count++) {
        if (app.customersReturnsList[count].id === customer_id) {
          app.customersReturnsList.splice(count, 1);
        }
      }
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
        // app.returned_items[index].main_amount = parseFloat(
        //   quantity * main_unit_rate,
        // ).toFixed(2);// + parseFloat(tax);
        // app.returned_items[index].quantity_supplied = quantity;
      }

      // we now calculate the running total of items invoiceed for with tax //////////
      // let total_tax = 0;
    //   let subtotal = 0;
    //   for (let count = 0; count < app.returned_items.length; count++) {
    //     // const tax_rate = app.returned_items[count].tax;
    //     // const quantity = app.returned_items[count].quantity;
    //     // const unit_rate = app.returned_items[count].rate;
    //     // total_tax += parseFloat(tax_rate * quantity * unit_rate);
    //     subtotal += parseFloat(app.returned_items[count].amount);
    //   }
    //   // app.form.tax = total_tax.toFixed(2);
    //   app.form.subtotal = subtotal.toFixed(2);
    //   app.form.discount = parseFloat(
    //     (app.discount_rate / 100) * subtotal,
    //   ).toFixed(2);
    //   // subtract discount
    //   app.form.amount = parseFloat(subtotal - app.form.discount).toFixed(2);
    },
  },
};
</script>
