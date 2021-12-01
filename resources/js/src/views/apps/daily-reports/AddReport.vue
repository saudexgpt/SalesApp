
<template>
  <el-card>
    <!-- tab 1 content -->
    <div title="Report Date" class="mb-5" icon="el-icon-date">
      <strong>Pick Report Date</strong>
      <el-date-picker
        v-model="form.date"
        :picker-options="pickerOptions"
        type="date"
        placeholder="Report Date"
        style="width: 100%;"
        format="dd-MM-yyyy"
        value-format="yyyy-MM-dd"
        @input="visitedCustomers()"
      />
    </div>
    <form-wizard v-loading="loadForm" v-if="form.date !== ''" :title="null" :subtitle="null" color="rgba(var(--vs-primary), 1)" finish-button-text="Submit" @on-complete="formSubmitted">

      <tab-content title="Sales Report" class="mb-5" icon="feather icon-shopping-cart">
        <div v-if="show_reported_message">
          <reported-message />
        </div>
        <div v-else>

          <sales-report :visited-customers-list="customersSalesList" :my-customers="my_customers" :products="products" />
        </div>
      </tab-content>
      <tab-content title="Collection Report" class="mb-5" icon="el-icon-money">
        <div v-if="show_reported_message">
          <reported-message />
        </div>
        <div v-else>
          <!-- tab 2 content -->
          <collection-report :visited-customers-list="customersCollectionList" :my-customers="my_customers" />
        </div>
      </tab-content>

      <!-- tab 3 content -->
      <tab-content title="Hospital Visit Report" class="mb-5" icon="el-icon-office-building">
        <div v-if="show_reported_message">
          <reported-message />
        </div>
        <div v-else>
          <hospital-visit-report :visited-customers-list="visitedHospitalsList" :my-customers="my_customers" :products="products" />
        </div>
      </tab-content>
      <tab-content title="General Report" class="mb-5" icon="el-icon-guide">
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
      </tab-content>
      <el-button slot="prev" type="danger">Back</el-button>
      <el-button slot="next" type="primary">Next</el-button>
      <el-button slot="finish" type="success">Submit</el-button>
    </form-wizard>
  </el-card>
</template>

<script>
import { FormWizard, TabContent } from 'vue-form-wizard';
import 'vue-form-wizard/dist/vue-form-wizard.min.css';
import ReportedMessage from './partials/ReportedMessage';
import CollectionReport from './partials/CollectionReport';
import SalesReport from './partials/SalesReport';
import HospitalVisitReport from './partials/HospitalVisitReport';
import Resource from '@/api/resource';
export default {
  components: {
    FormWizard,
    TabContent,
    ReportedMessage,
    CollectionReport,
    SalesReport,
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
      my_customers: [],
      products: [],
      form: {
        work_with_manager_check: '0',
        time_duration_with_manager: null,
        relationship_with_manager: null,
        date: '',
        customer_sales: [],
        customer_collections: [],
        hospital_report: [],
      },
      show_reported_message: false,
      loadForm: false,
    };
  },
  created() {
    // this.visitedCustomers();
    this.fetchMyProducts();
  },
  methods: {
    fetchMyProducts() {
      const app = this;
      const getProducts = new Resource('products/my-products');
      getProducts.list().then((response) => {
        app.products = response.products;
      });
    },
    visitedCustomers() {
      const app = this;
      const getCustomers = new Resource('daily-report/visited-customers');
      const param = { date: app.form.date };
      app.customersCollectionList = [];
      app.customersSalesList = [];
      app.visitedHospitalsList = [];
      app.show_reported_message = false;
      app.loadForm = true;
      getCustomers.list(param).then((response) => {
        if (response.message === 'reported') {
          app.show_reported_message = true;
        } else {
          const visits = response.visits;
          visits.forEach(element => {
            element.customer.customer_id = element.customer.id;
            element.customer.payment_mode = 'later';
            app.customersCollectionList.push(element.customer);
            app.customersSalesList.push(element.customer);
            // Hospital has customer_type_id to be 1
            if (element.customer.customer_type_id === 1) {
              app.visitedHospitalsList.push(element.customer);
            }
          });
          app.my_customers = response.my_customers;
        }
        app.loadForm = false;
      });
    },
    formSubmitted() {
      const app = this;
      app.$confirm('Are you sure you want to submit this report? It cannot be modified', 'Warning', {
        confirmButtonText: 'Yes Submit',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        this.$message({
          type: 'info',
          message: 'Sending...',
        });
        const param = app.form;
        const unsaved_orders = { unsaved_orders: app.customersSalesList };
        const submitSales = new Resource('sales/store');
        submitSales.store(unsaved_orders).then((response) => {
          console.log(response);
        });

        param.customers_report = app.customersCollectionList;
        param.hospital_report = app.visitedHospitalsList;
        const submitReport = new Resource('daily-report/store');
        submitReport.store(param).then((response) => {
          this.$message({
            type: 'success',
            message: 'Report Submitted',
          });

          console.log(response);
        });
      }).catch(() => {});
    },
  },
};
</script>

