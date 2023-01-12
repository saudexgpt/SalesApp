<template>
  <div v-loading="load" class="vx-row">
    <aside>Kindly note that this part is strictly for addding Initial Debts of Customers belonging to a Rep. Other Sales Transactions should be entered under <code>Sales</code></aside>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Action</th>
          <th>Rep</th>
          <th>Customer</th>
          <th>Debt (NGN)</th>
          <th>Date of Debt</th>
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
          </td>
          <!-- <td>0</td> -->
          <td>
            <!-- {{ customer.amount }} -->
            <el-input
              v-model="rep_entry.amount"
              placeholder="Enter Amount"
              type="number"
            />
          </td>
          <td>
            <el-date-picker
              v-model="rep_entry.created_at"
              :picker-options="pickerOptions"
              type="date"
              placeholder="Date of Debt"
              style="width: 100%;"
              format="yyyy-MM-dd"
              value-format="yyyy-MM-dd"
            />
          </td>
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
            <el-button round type="success" @click="submitDebtors()">Submit Debtors Report</el-button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script>
import Resource from '@/api/resource';

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
          detail.amount === '' ||
          detail.created_at === ''
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
          customer_details: {},
          business_name: '',
          customer_id: '',
          customersList: [],
          created_at: new Date('2022-12-31'),
          amount: '',
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
    requestCustomer(index) {
      const app = this;
      app.rowIndex = index;
      app.$emit('selectCustomer', 'debtor');
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
    submitDebtors() {
      const app = this;
      app.$confirm('Are you sure you want to submit these debtors?', 'Warning', {
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
              created_at: entry.created_at,
              amount: entry.amount,
              // invoice_items: entry.invoice_items,
              // main_amount: entry.main_amount,
            });
          });
          const unsaved_orders = { debtors: formattedEntries };
          const submitSales = new Resource('customers/load-debts');
          submitSales.store(unsaved_orders).then(() => {
            this.$message({
              type: 'success',
              message: 'Debtors List Submitted',
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
