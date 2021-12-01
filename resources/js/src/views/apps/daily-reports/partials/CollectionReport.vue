<template>
  <div class="vx-row">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Customer Name</th>
          <!-- <th>Opening Debt</th> -->
          <th>Amount Collected (NGN)</th>
          <th>Payment Method</th>
          <!-- <th>Closing Debt</th> -->
        </tr>
      </thead>
      <tbody>
        <tr v-for="(customer, index) in visitedCustomersList" :key="index">
          <td>
            {{ customer.business_name }}
            <el-button circle type="danger" icon="el-icon-delete" @click="removeExtraCustomer(customer.id)" />
          </td>
          <!-- <td>0</td> -->
          <td>
            <el-input
              v-model="customer.amount_collected"
              type="number"
              outline
              placeholder="Amount Collected"
              min="1"
            />
          </td>
          <td>
            <el-select
              v-model="customer.payment_method"
              style="width: 100%"
            >
              <el-option value="Cash" label="Cash" />
              <el-option value="Cheque" label="Cheque" />
              <el-option value="Bank Deposit/Transfer" label="Bank Deposit/Transfer" />
            </el-select>
          </td>
          <!-- <td>0</td> -->
        </tr>
        <tr>
          <td colspan="3">
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
  </div>
</template>
<script>
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
  },
  data() {
    return {
      collectionForm: {
        amount_collected: 0,
        opening_debt: 0,
        closing_debt: 0,
        payment_method: 'Cash',
      },

      extra_customers: '',
    };
  },
  methods: {
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
