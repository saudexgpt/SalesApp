<template>
  <div class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Action</th>
          <th>Customer Name</th>
          <!-- <th>Opening Debt</th> -->
          <th>Total Sales (NGN)</th>
          <th>Payment Due Date</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(customer, index) in visitedCustomersList" :key="index">
          <td>
            <el-tooltip :content="'Add Sales Report for ' + customer.business_name" class="item" effect="dark" placement="top-start">
              <el-button circle type="primary" icon="el-icon-goods" @click="setCustomerSales(index, customer)" />
            </el-tooltip>
          </td>
          <td>
            {{ customer.business_name }}
            <el-button circle type="danger" icon="el-icon-delete" @click="removeExtraCustomer(customer.id)" />
          </td>
          <!-- <td>0</td> -->
          <td>{{ customer.amount }}</td>
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
          </td>
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
                v-for="(customer, item_index) in myCustomers"
                :key="item_index"
                :value="item_index"
                :label="customer.business_name"
              />
            </el-select>
          </td>
        </tr>
      </tbody>
    </table>
    <el-dialog
      :visible.sync="dialogVisible"
      :title="'Sales for ' + customer_name"
      width="90%">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th/>
            <th>Product</th>
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
            <td>
              <el-select
                v-model="sale.item_index"
                placeholder="Select Product"
                filterable
                style="width: 100%"
                @input="fetchItemDetails(index)"
              >
                <el-option
                  v-for="(product, item_index) in products"
                  :key="item_index"
                  :value="item_index"
                  :label="product.item.name + ' (Bal: ' + product.total_balance + ')'"
                >
                  <span style="float: left">{{ product.item.name }}</span>
                  <span style="float: right; color: #8492a6; font-size: 13px">{{ ' (Bal: ' + product.total_balance + ')' }}</span>
                </el-option>
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
                @blur="deductProduct(index);"
              />
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
        <el-button @click="clearForm()">Clear</el-button>
        <el-button type="danger" @click="dialogVisible = false">Cancel</el-button>
        <el-button v-if="showSaveButton" type="primary" @click="addCustomerSales()">Done</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
// import Resource from '@/api/resource';
export default {
  props: {
    visitedCustomersList: {
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
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate() - 1);
          return date < d;
        },
      },
      activeName: '1',
      invoice_items: [],
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
    // fetchMyProducts() {
    //   const app = this;
    //   const getProducts = new Resource('products/my-products');
    //   getProducts.list().then((response) => {
    //     app.products = response.products;
    //   });
    // },
    setCustomerSales(index, customer) {
      this.selected_index = index;
      this.invoice_items = [];
      if (!customer.invoice_items) {
        this.addLine();
      } else {
        this.invoice_items = customer.invoice_items;
      }
      this.customer_name = customer.business_name;
      this.dialogVisible = true;
    },
    addCustomerSales() {
      const app = this;
      const invoice_items = app.invoice_items;
      var total_sales = 0;
      for (let count = 0; count < invoice_items.length; count++) {
        // const tax_rate = app.invoice_items[count].tax;
        // const quantity = app.invoice_items[count].quantity;
        // const unit_rate = app.invoice_items[count].rate;
        // total_tax += parseFloat(tax_rate * quantity * unit_rate);
        total_sales += parseFloat(app.invoice_items[count].amount);
      }
      app.visitedCustomersList[app.selected_index].amount = total_sales;
      app.visitedCustomersList[app.selected_index].invoice_items = invoice_items;
      // console.log(app.visitedCustomersList[app.selected_index]);
      this.dialogVisible = false;
    },
    addLine() {
      this.fill_fields_error = false;

      const checkEmptyLines = this.invoice_items.filter(
        (detail) =>
          detail.item_id === '' ||
          detail.quantity === '' ||
          detail.rate === ''
      );

      if (checkEmptyLines.length >= 1 && this.invoice_items.length > 0) {
        this.fill_fields_error = true;
        // this.invoice_items[index].seleted_category = true;
        return;
      } else {
        // if (this.invoice_items.length > 0)
        //     this.invoice_items[index].grade = '';
        this.invoice_items.push({
          item_index: null,
          item_id: '',
          quantity: 0,
          rate: '',
          delivery_mode: 'now',
          quantity_supplied: 0,
          amount: 0,
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
    deductProduct(index) {
      const app = this;
      const item_index = app.invoice_items[index].item_index;
      const quantity = parseInt(app.invoice_items[index].quantity);
      const quantity_supplied = app.invoice_items[index].quantity_supplied;
      let balance = parseInt(app.products[item_index].total_balance) + parseInt(quantity_supplied);

      if (balance >= quantity){
        balance -= parseInt(quantity);

        app.invoice_items[index].quantity_supplied = quantity;
      } else {
        app.invoice_items[index].quantity = 0;

        app.invoice_items[index].quantity_supplied = 0;
        // balance += (quantity_supplied) ? parseInt(quantity_supplied) : 0;
        app.$alert('You are out of van product for supply. Kindly restock your van. You can do that under Inventory Menu');
      }

      app.products[item_index].total_balance = balance;
      // const item = app.products[item_index].item;
    },
    clearForm() {
      this.invoice_items = [];
      this.addLine();
      this.addCustomerSales();
    },
    fetchItemDetails(index) {
      const app = this;
      const item_index = app.invoice_items[index].item_index;
      const item = app.products[item_index].item;
      app.invoice_items[index].rate = item.price.sale_price;
      app.invoice_items[index].item_id = item.id;
      app.invoice_items[index].type = item.package_type;
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
        const quantity = app.invoice_items[index].quantity;
        const unit_rate = app.invoice_items[index].rate;
        app.invoice_items[index].amount = parseFloat(
          quantity * unit_rate,
        ).toFixed(2); // + parseFloat(tax);
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
    addExtraCustomers(value) {
      const app = this;
      const customer_id = app.myCustomers[value].id;
      if (!app.visitedCustomersList.filter(e => e.id === customer_id).length > 0) {
        app.myCustomers[value].customer_id = customer_id;
        app.myCustomers[value].payment_mode = 'later';
        app.visitedCustomersList.push(app.myCustomers[value]);
      }
    },
    removeExtraCustomer(customer_id) {
      const app = this;
      for (let count = 0; count < app.visitedCustomersList.length; count++) {
        if (app.visitedCustomersList[count].id === customer_id) {
          app.visitedCustomersList.splice(count, 1);
        }
      }
    },
  },
};
</script>
