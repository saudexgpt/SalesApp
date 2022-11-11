
<template>
  <el-card>
    <!-- tab 1 content -->
    <div title="Report Date" class="mb-5" icon="el-icon-date">
      <filter-options :hide-customers-list="true" :submit-on-rep-change="true" panel="none" @submitQuery="setFormParams" />
      <!-- <strong>Pick Report Date</strong>
      <el-date-picker
        v-model="form.date"
        :picker-options="pickerOptions"
        type="date"
        placeholder="Report Date"
        style="width: 100%;"
        format="dd-MM-yyyy"
        value-format="yyyy-MM-dd"
        @input="visitedCustomers()"
      /> -->
    </div>
    <form-wizard v-loading="loadForm" v-if="form.rep_id !== ''" :title="null" :subtitle="null" color="rgba(var(--vs-primary), 1)" finish-button-text="Submit" @on-complete="formSubmitted">

      <tab-content :before-change="()=>validateSales()" title="New Sales" class="mb-5" icon="feather icon-shopping-cart">
        <div v-if="show_reported_message">
          <reported-message />
        </div>
        <div v-else>

          <sales-report :visited-customers-list="customersSalesList" :my-customers="my_customers" :products="products" />
        </div>
      </tab-content>
      <tab-content :before-change="()=>validateCollections()" title="New Collections" class="mb-5" icon="el-icon-money">
        <div v-if="show_reported_message">
          <reported-message />
        </div>
        <div v-else>
          <!-- tab 2 content -->
          <collection-report :visited-customers-list="customersCollectionList" :my-customers="my_customers" />
        </div>
      </tab-content>
      <tab-content :before-change="()=>validateReturns()" title="Products Returned" class="mb-5" icon="el-icon-sell">
        <div v-if="show_reported_message">
          <reported-message />
        </div>
        <div v-else>
          <!-- tab 2 content -->
          <returns-report :customers-returns-list="customersReturnsList" :my-customers="my_customers" :products="all_products" />
        </div>
      </tab-content>

      <!-- tab 3 content -->
      <!-- <tab-content :before-change="()=>validateHospitalVisit()" title="Detailing Report" class="mb-5" icon="el-icon-office-building">
        <div v-if="show_reported_message">
          <reported-message />
        </div>
        <div v-else>
          <hospital-visit-report :visited-customers-list="visitedHospitalsList" :my-customers="my_customers" :products="all_products" />
        </div>
      </tab-content> -->
      <!-- <tab-content title="General Report" class="mb-5" icon="el-icon-guide">
        <div v-if="show_reported_message">
          <reported-message />
        </div>
        <div v-else>

          <div class="vx-row">
            <div class="vx-col md:w-1/2 w-full">
              <strong>Did you work with your manager today?</strong><br>
              <el-radio v-model="form.work_with_manager_check" label="0">No</el-radio>
              <el-radio v-model="form.work_with_manager_check" label="1">Yes</el-radio>
            </div>
            <div v-if="form.work_with_manager_check === '1'" class="vx-col md:w-1/2 w-full">
              <div>

                <strong>How long did you work with your manager today in hours?</strong>
                <el-input
                  v-model="form.time_duration_with_manager"
                  type="number"
                  outline
                  placeholder="Type time spent with manager today"
                >
                  <template slot="append">Hour(s)</template>
                </el-input>
              </div>
            </div>
            <div v-if="form.work_with_manager_check === '1'" class="vx-col md:w-1/2 w-full mt-5">
              <div>
                <strong>How was your relationship with your manager today?</strong>
                <el-input
                  v-model="form.relationship_with_manager"
                  type="textarea"
                  maxlength="160"
                  placeholder="Briefly describe your manager-staff relationship today"
                  show-word-limit
                />
              </div>
            </div>
            <div class="vx-col md:w-1/2 w-full md:mt-8">
              <div>
                <strong>Market Feedback</strong>
                <el-input
                  v-model="form.market_feedback"
                  type="textarea"
                  maxlength="160"
                  placeholder="Give brief description of what the market says about us today"
                  show-word-limit
                />
              </div>
            </div>
          </div>
        </div>
      </tab-content> -->
      <el-button slot="prev" type="danger">Back</el-button>
      <el-button slot="next" type="primary">Next</el-button>
      <el-button slot="finish" :disabled="show_reported_message" type="success">Submit</el-button>
    </form-wizard>
  </el-card>
</template>

<script>
import { FormWizard, TabContent } from 'vue-form-wizard';
import 'vue-form-wizard/dist/vue-form-wizard.min.css';
import FilterOptions from '@/views/apps/reports/FilterOptions';
import ReportedMessage from './partials/ReportedMessage';
import CollectionReport from './partials/CollectionReport';
import SalesReport from './partials/SalesReport';
import ReturnsReport from './partials/ReturnsReport';
import HospitalVisitReport from './partials/HospitalVisitReport';
import Resource from '@/api/resource';
export default {
  components: {
    FormWizard,
    TabContent,
    FilterOptions,
    ReportedMessage,
    CollectionReport,
    SalesReport,
    ReturnsReport,
    HospitalVisitReport,
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
      customersCollectionList: [],
      customersSalesList: [],
      visitedHospitalsList: [],
      customersReturnsList: [],
      my_hospital_customers: [],
      my_customers: [],
      products: [],
      all_products: [],
      form: {
        customer_id: '',
        rep_id: '',
        team_id: '',
        date: '',
      },
      show_reported_message: false,
      loadForm: false,
    };
  },
  created() {
    // this.visitedCustomers();
    // this.fetchRepProducts();
  },
  methods: {
    setFormParams(param){
      const app = this;
      app.customersCollectionList = [];
      app.customersSalesList = [];
      app.visitedHospitalsList = [];
      app.customersReturnsList = [];
      app.my_customers = [];
      app.form = param;
      app.my_customers = param.customers;
      this.fetchRepProducts();
    },
    validateSales() {
      const sales = this.customersSalesList;
      if (sales.length > 0) {
        let errorCount = 0;
        sales.forEach(sale => {
          if (sale.entry_date === '' || sale.entry_date === undefined) {
            errorCount++;
          }
          if (sale.rep_coordinate === '' || sale.rep_coordinate === undefined) {
            errorCount++;
          }
        //   if (sale.invoice_items === undefined) {
        //     errorCount++;
        //   } else {
        //     const checkEmptyLines = sale.invoice_items.filter(
        //       (detail) => detail.item_id === '' || detail.quantity === '' || detail.quantity === 0 || detail.rate === ''
        //     );
        //     if (checkEmptyLines.length > 0) {
        //       errorCount++;
        //     }
        //   }
        });
        if (errorCount > 0) {
          this.$alert('Kindly fill all empty fields under Sales Report before continuing');
          return false;
        }
      }

      return true;
    },
    validateCollections() {
      const collections = this.customersCollectionList;
      if (collections.length > 0) {
        let errorCount = 0;
        collections.forEach(sale => {
          if (sale.amount_collected === '' || sale.payment_mode === undefined) {
            errorCount++;
          }
        });
        if (errorCount > 0) {
          this.$alert('Kindly fill all empty fields under Collections Report before continuing');
          return false;
        }
      }

      return true;
    },
    validateReturns() {
      const returnsList = this.customersReturnsList;
      if (returnsList.length > 0) {
        let errorCount = 0;
        returnsList.forEach(item => {
          if (item.returns === undefined) {
            errorCount++;
          } else {
            const checkEmptyLines = item.returns.filter(
              (detail) => detail.product_id === '' || detail.quantity_returned === '' || detail.quantity === 0 || detail.rate === '' || /* detail.batch_no === '' ||*/ detail.expiry_date === '' || detail.reason === ''
            );
            if (checkEmptyLines.length > 0) {
              errorCount++;
            }
          }
        });
        if (errorCount > 0) {
          this.$alert('Kindly fill all empty fields under Product Returned Report before continuing');
          return false;
        }
      }

      return true;
    },
    validateHospitalVisit() {
      const hospitalVisits = this.visitedHospitalsList;
      console.log(hospitalVisits);
      if (hospitalVisits.length > 0) {
        let errorCount = 0;
        hospitalVisits.forEach(item => {
          if (item.hospital_visit_details === undefined) {
            errorCount++;
          } else {
            const checkEmptyLines = item.hospital_visit_details.filter(
              (detail) => detail.hospital_contacts === '' ||
          detail.hospital_feedback === '' ||
          detail.marketed_products_to_hospitals.length < 1
            );
            if (checkEmptyLines.length > 0) {
              errorCount++;
            }
          }
        });
        if (errorCount > 0) {
          this.$alert('Kindly fill all empty fields under Hospital Visit Report before continuing');
          return false;
        }
      }

      return true;
    },
    fetchRepProducts() {
      const app = this;
      const getProducts = new Resource('products/rep-products');
      const param = {
        rep_id: app.form.rep_id,
        team_id: app.form.team_id,
      };
      getProducts.list(param).then((response) => {
        app.products = response.rep_products;
        app.all_products = response.team_products;
      });
    },
    visitedCustomers() {
      const app = this;
      const getCustomers = new Resource('daily-report/visited-customers');
      const param = { date: app.form.date };
      app.customersCollectionList = [];
      app.customersSalesList = [];
      app.visitedHospitalsList = [];
      app.customersReturnsList = [];
      app.show_reported_message = false;
      app.loadForm = true;
      getCustomers.list(param).then((response) => {
        if (response.message === 'reported') {
          app.show_reported_message = true;
        } else {
          const visits = response.visits;
          visits.forEach(element => {
            const details = element.details;

            let purpose_string = ''; // element.purpose.split(',');
            details.forEach(detail => {
              purpose_string = purpose_string.concat(',', detail.purpose);
            });
            const purposes = purpose_string.split(',');
            element.customer.customer_id = element.customer.id;
            // element.customer.payment_mode = 'Cash';
            element.customer.can_delete = 'no';
            if (purposes.includes('Collections')) {
              app.customersCollectionList.push(element.customer);
            }
            if (purposes.includes('Sales')) {
              app.customersSalesList.push(element.customer);
            }
            if (purposes.includes('Returns')) {
              app.customersReturnsList.push(element.customer);
            }
            // Hospital has customer_type_id to be 1
            if (element.customer.customer_type_id === 1) {
              app.visitedHospitalsList.push(element.customer);
            }
          });
          const my_customers = response.my_customers;
          my_customers.forEach(element => {
            // Hospital has customer_type_id to be 1
            if (element.customer_type_id === 1) {
              app.my_hospital_customers.push(element);
            }
          });
          app.my_customers = response.my_customers;
        }
        app.loadForm = false;
      });
    },
    formSubmitted() {
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
        app.loadForm = true;
        const param = app.form;
        if (app.customersSalesList.length > 0) {
          const unsaved_orders = { unsaved_orders: app.customersSalesList, date: param.date, rep_id: param.rep_id };
          const submitSales = new Resource('sales/store');
          submitSales.store(unsaved_orders).then(() => {
            this.$message({
              type: 'success',
              message: 'Sales Entries Submitted',
            });
            // app.customersSalesList = [];
            app.loadForm = false;
          });
        }
        if (app.customersCollectionList.length > 0) {
          const unsaved_payments = { unsaved_collections: app.customersCollectionList, date: param.date, rep_id: param.rep_id };
          const submitCollections = new Resource('payments/store');
          submitCollections.store(unsaved_payments).then(() => {
            this.$message({
              type: 'success',
              message: 'Collections Entries Submitted',
            });
            app.customersCollectionList = [];
          });
        }
        if (app.customersReturnsList.length > 0) {
          const unsaved_returns = { unsaved_returns: app.customersReturnsList, date: param.date, rep_id: param.rep_id };
          const storeResource = new Resource('returns/store');
          storeResource.store(unsaved_returns).then(() => {
            this.$message({
              type: 'success',
              message: 'Returns Entries Submitted',
            });
            app.customersReturnsList = [];
            app.loadForm = false;
          });
        }
        // app.loadForm = false;
      }).catch(() => {
        app.loadForm = false;
      });
    },
  },
};
</script>

