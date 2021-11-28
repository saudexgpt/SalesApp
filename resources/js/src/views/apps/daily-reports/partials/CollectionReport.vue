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
          <td>{{ customer.business_name }}</td>
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
    };
  },
  methods: {
  },
};
</script>
