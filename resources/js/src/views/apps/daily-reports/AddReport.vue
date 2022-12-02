
<template>
  <el-card>
    <div slot="header" class="clearfix">
      <span>Transaction Entry Form</span>
      <span class="pull-right">
        <el-button circle type="danger" icon="el-icon-refresh" @click="loadCustomers()" />
      </span>

    </div>
    <el-row :gutter="10">
      <el-col :lg="12" :md="12" :sm="24" :xs="24">
        <label for="">Select Team</label>
        <el-select v-model="form.team_id" filterable style="width: 100%" @change="fetchTeamReps($event)">
          <el-option
            v-for="(team, index) in teams"
            :key="index"
            :label="team.name"
            :value="team.id"

          />
        </el-select>
      </el-col>
    </el-row>
    <br><br>
    <el-tabs type="border-card">
      <el-tab-pane label="Sales">
        <sales-report :team-id="form.team_id" :reps="reps" :customers-list="customersList" />
      </el-tab-pane>
      <el-tab-pane label="Collections">
        <collection-report :team-id="form.team_id" :reps="reps" :customers-list="customersList" />
      </el-tab-pane>
      <el-tab-pane label="Detailing/Clinical Meetings">
        <hospital-visit-report :team-id="form.team_id" :reps="reps" :customers-list="customersList" />
      </el-tab-pane>
      <el-tab-pane label="Returns">
        <returns-report :team-id="form.team_id" :reps="reps" :customers-list="customersList" />
      </el-tab-pane>
      <el-tab-pane label="Others">
        <other-purposes :team-id="form.team_id" :reps="reps" :customers-list="customersList" />
      </el-tab-pane>
      <el-tab-pane label="Managers' Entries">
        <managers-entries :team-id="form.team_id" :reps="reps" :customers-list="customersList" />
      </el-tab-pane>
    </el-tabs>
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
import OtherPurposes from './partials/OtherPurposes';
import ManagersEntries from './partials/ManagersEntries';
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
    OtherPurposes,
    ManagersEntries,
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
        team_id: null,
        date: '',
      },
      teams: [],
      reps: [],
      show_reported_message: false,
      loadForm: false,
    };
  },
  computed: {
    customersList() {
      return this.$store.getters.customers;
    },
  },
  created() {
    this.fetchTeams();
    this.loadCustomers();
  },
  methods: {
    fetchTeams() {
      const app = this;
      // this.load_table = true;
      const salesRepResource = new Resource('teams');
      salesRepResource
        .list()
        .then((response) => {
          app.teams = response.teams;
          if (app.teams.length > 0) {
            app.form.team_id = app.teams[0].id;
            app.fetchTeamReps(app.form.team_id);
          }
        })
        .catch((error) => {
          console.log(error);
        });
    },
    fetchTeamReps(teamId) {
      const app = this;
      // this.load_table = true;
      app.reps = [];
      app.form.rep_id = '';
      const salesRepResource = new Resource('teams/fetch-reps');
      salesRepResource
        .list({ team_id: teamId })
        .then((response) => {
          app.reps = response.team_reps;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    loadCustomers(){
      const app = this;
      app.$store.dispatch('customer/fetch').then(app.$message('Customers Reloaded'));
    },
    // setFormParams(param){
    //   const app = this;
    //   app.customersCollectionList = [];
    //   app.customersSalesList = [];
    //   app.visitedHospitalsList = [];
    //   app.customersReturnsList = [];
    //   app.my_customers = [];
    //   app.form = param;
    //   app.my_customers = param.customers;
    //   this.fetchRepProducts();
    // },
    // validateSales() {
    //   const sales = this.customersSalesList;
    //   if (sales.length > 0) {
    //     let errorCount = 0;
    //     sales.forEach(sale => {
    //       if (sale.entry_date === '' || sale.entry_date === undefined) {
    //         errorCount++;
    //       }
    //       if (sale.rep_coordinate === '' || sale.rep_coordinate === undefined) {
    //         errorCount++;
    //       }
    //     //   if (sale.invoice_items === undefined) {
    //     //     errorCount++;
    //     //   } else {
    //     //     const checkEmptyLines = sale.invoice_items.filter(
    //     //       (detail) => detail.item_id === '' || detail.quantity === '' || detail.quantity === 0 || detail.rate === ''
    //     //     );
    //     //     if (checkEmptyLines.length > 0) {
    //     //       errorCount++;
    //     //     }
    //     //   }
    //     });
    //     if (errorCount > 0) {
    //       this.$alert('Kindly fill all empty fields under Sales Report before continuing');
    //       return false;
    //     }
    //   }

    //   return true;
    // },
    // validateCollections() {
    //   const collections = this.customersCollectionList;
    //   if (collections.length > 0) {
    //     let errorCount = 0;
    //     collections.forEach(sale => {
    //       if (sale.amount_collected === '' || sale.payment_mode === undefined) {
    //         errorCount++;
    //       }
    //     });
    //     if (errorCount > 0) {
    //       this.$alert('Kindly fill all empty fields under Collections Report before continuing');
    //       return false;
    //     }
    //   }

    //   return true;
    // },
    // validateReturns() {
    //   const returnsList = this.customersReturnsList;
    //   if (returnsList.length > 0) {
    //     let errorCount = 0;
    //     returnsList.forEach(item => {
    //       if (item.returns === undefined) {
    //         errorCount++;
    //       } else {
    //         const checkEmptyLines = item.returns.filter(
    //           (detail) => detail.product_id === '' || detail.quantity_returned === '' || detail.quantity === 0 || detail.rate === '' || /* detail.batch_no === '' ||*/ detail.expiry_date === '' || detail.reason === ''
    //         );
    //         if (checkEmptyLines.length > 0) {
    //           errorCount++;
    //         }
    //       }
    //     });
    //     if (errorCount > 0) {
    //       this.$alert('Kindly fill all empty fields under Product Returned Report before continuing');
    //       return false;
    //     }
    //   }

    //   return true;
    // },
    // validateHospitalVisit() {
    //   const hospitalVisits = this.visitedHospitalsList;
    //   console.log(hospitalVisits);
    //   if (hospitalVisits.length > 0) {
    //     let errorCount = 0;
    //     hospitalVisits.forEach(item => {
    //       if (item.hospital_visit_details === undefined) {
    //         errorCount++;
    //       } else {
    //         const checkEmptyLines = item.hospital_visit_details.filter(
    //           (detail) => detail.hospital_contacts === '' ||
    //       detail.hospital_feedback === '' ||
    //       detail.marketed_products_to_hospitals.length < 1
    //         );
    //         if (checkEmptyLines.length > 0) {
    //           errorCount++;
    //         }
    //       }
    //     });
    //     if (errorCount > 0) {
    //       this.$alert('Kindly fill all empty fields under Hospital Visit Report before continuing');
    //       return false;
    //     }
    //   }

    //   return true;
    // },
    // fetchRepProducts() {
    //   const app = this;
    //   const getProducts = new Resource('products/rep-products');
    //   const param = {
    //     rep_id: app.form.rep_id,
    //     team_id: app.form.team_id,
    //   };
    //   getProducts.list(param).then((response) => {
    //     app.products = response.rep_products;
    //     app.all_products = response.team_products;
    //   });
    // },
    // visitedCustomers() {
    //   const app = this;
    //   const getCustomers = new Resource('daily-report/visited-customers');
    //   const param = { date: app.form.date };
    //   app.customersCollectionList = [];
    //   app.customersSalesList = [];
    //   app.visitedHospitalsList = [];
    //   app.customersReturnsList = [];
    //   app.show_reported_message = false;
    //   app.loadForm = true;
    //   getCustomers.list(param).then((response) => {
    //     if (response.message === 'reported') {
    //       app.show_reported_message = true;
    //     } else {
    //       const visits = response.visits;
    //       visits.forEach(element => {
    //         const details = element.details;

    //         let purpose_string = ''; // element.purpose.split(',');
    //         details.forEach(detail => {
    //           purpose_string = purpose_string.concat(',', detail.purpose);
    //         });
    //         const purposes = purpose_string.split(',');
    //         element.customer.customer_id = element.customer.id;
    //         // element.customer.payment_mode = 'Cash';
    //         element.customer.can_delete = 'no';
    //         if (purposes.includes('Collections')) {
    //           app.customersCollectionList.push(element.customer);
    //         }
    //         if (purposes.includes('Sales')) {
    //           app.customersSalesList.push(element.customer);
    //         }
    //         if (purposes.includes('Returns')) {
    //           app.customersReturnsList.push(element.customer);
    //         }
    //         // Hospital has customer_type_id to be 1
    //         if (element.customer.customer_type_id === 1) {
    //           app.visitedHospitalsList.push(element.customer);
    //         }
    //       });
    //       const my_customers = response.my_customers;
    //       my_customers.forEach(element => {
    //         // Hospital has customer_type_id to be 1
    //         if (element.customer_type_id === 1) {
    //           app.my_hospital_customers.push(element);
    //         }
    //       });
    //       app.my_customers = response.my_customers;
    //     }
    //     app.loadForm = false;
    //   });
    // },
    // formSubmitted() {
    //   const app = this;
    //   app.$confirm('Are you sure you want to submit these entries?', 'Warning', {
    //     confirmButtonText: 'Yes Submit',
    //     cancelButtonText: 'Cancel',
    //     type: 'warning',
    //   }).then(() => {
    //     this.$message({
    //       type: 'info',
    //       message: 'Sending...',
    //     });
    //     app.loadForm = true;
    //     const param = app.form;
    //     if (app.customersSalesList.length > 0) {
    //       const unsaved_orders = { unsaved_orders: app.customersSalesList, date: param.date, rep_id: param.rep_id };
    //       const submitSales = new Resource('sales/store');
    //       submitSales.store(unsaved_orders).then(() => {
    //         this.$message({
    //           type: 'success',
    //           message: 'Sales Entries Submitted',
    //         });
    //         app.customersSalesList = [];
    //         app.loadForm = false;
    //       });
    //     }
    //     if (app.customersCollectionList.length > 0) {
    //       const unsaved_payments = { unsaved_collections: app.customersCollectionList, date: param.date, rep_id: param.rep_id };
    //       const submitCollections = new Resource('payments/store');
    //       submitCollections.store(unsaved_payments).then(() => {
    //         this.$message({
    //           type: 'success',
    //           message: 'Collections Entries Submitted',
    //         });
    //         app.customersCollectionList = [];
    //       });
    //     }
    //     if (app.customersReturnsList.length > 0) {
    //       const unsaved_returns = { unsaved_returns: app.customersReturnsList, date: param.date, rep_id: param.rep_id };
    //       const storeResource = new Resource('returns/store');
    //       storeResource.store(unsaved_returns).then(() => {
    //         this.$message({
    //           type: 'success',
    //           message: 'Returns Entries Submitted',
    //         });
    //         app.customersReturnsList = [];
    //         app.loadForm = false;
    //       });
    //     }
    //     // app.loadForm = false;
    //   }).catch(() => {
    //     app.loadForm = false;
    //   });
    // },
  },
};
</script>

