
<template>
  <el-card>
    <div slot="header" class="clearfix">
      <span>Transaction Entry Form</span>
      <span class="pull-right">
        <el-button :loading="loadButton" circle type="danger" icon="el-icon-refresh" @click="loadCustomers(); loadProducts();" />
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
    <el-dialog
      :visible.sync="dialogVisible"
      title="Search Customers"
      width="90%">
      <v-client-table
        v-model="customersList"
        :columns="['action', 'id', 'business_name', 'code', 'area']"
        :options="options"
      >
        <template slot="action" slot-scope="props">
          <el-button
            round
            class="filter-item"
            type="danger"
            @click="sendCustomer(props.row, type)"
          >Select
          </el-button>
        </template>
      </v-client-table>
    </el-dialog>
    <el-tabs type="border-card">
      <el-tab-pane label="Sales">
        <sales-report :team-id="form.team_id" :reps="reps" :selected-customer="salesSelectedCustomer" @selectCustomer="selectCustomer" />
      </el-tab-pane>
      <el-tab-pane label="Collections">
        <collection-report :team-id="form.team_id" :reps="reps" :selected-customer="collectionSelectedCustomer" @selectCustomer="selectCustomer"/>
      </el-tab-pane>
      <el-tab-pane label="Detailing/Clinical Meetings">
        <hospital-visit-report :team-id="form.team_id" :reps="reps" :selected-customer="detailingSelectedCustomer" @selectCustomer="selectCustomer"/>
      </el-tab-pane>
      <el-tab-pane label="Returns">
        <returns-report :team-id="form.team_id" :reps="reps" :selected-customer="returnsSelectedCustomer" @selectCustomer="selectCustomer"/>
      </el-tab-pane>
      <el-tab-pane label="Others">
        <other-purposes :team-id="form.team_id" :reps="reps" :selected-customer="othersSelectedCustomer" @selectCustomer="selectCustomer"/>
      </el-tab-pane>
      <el-tab-pane label="Managers' Entries">
        <managers-entries :team-id="form.team_id" :reps="reps" :selected-customer="managerSelectedCustomer" @selectCustomer="selectCustomer"/>
      </el-tab-pane>
      <el-tab-pane label="Add Debtors">
        <add-debtors :team-id="form.team_id" :reps="reps" :selected-customer="debtor" @selectCustomer="selectCustomer"/>
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
import AddDebtors from './partials/AddDebtors';
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
    AddDebtors,
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
      options: {
        headings: {
          action: '',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        filterByColumn: true,
        sortable: ['business_name', 'area'],
        filterable: ['business_name', 'area'],
      },
      salesSelectedCustomer: {},
      collectionSelectedCustomer: {},
      detailingSelectedCustomer: {},
      returnsSelectedCustomer: {},
      othersSelectedCustomer: {},
      managerSelectedCustomer: {},
      debtor: {},
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
      dialogVisible: false,
      type: '',
      loadButton: false,
    };
  },
  computed: {
    customersList() {
      return this.$store.getters.customers;
    },
  },
  created() {
    this.fetchTeams();
    // this.loadCustomers();
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
      app.loadButton = true;
      app.$store.dispatch('customer/fetch').then(() => {
        app.$message('Customers Reloaded');
        app.loadButton = false;
      });
    },
    loadProducts(){
      const app = this;
      app.loadButton = true;
      app.$store.dispatch('products/fetch').then(() => {
        app.$message('Products Reloaded');
        app.loadButton = false;
      });
    },
    selectCustomer(type){
      const app = this;
      app.type = type;
      app.dialogVisible = true;
    },
    sendCustomer(customer, type) {
      const app = this;
      switch (type) {
        case 'sales':
          app.salesSelectedCustomer = customer;
          break;
        case 'collections':
          app.collectionSelectedCustomer = customer;
          break;
        case 'detailing':
          app.detailingSelectedCustomer = customer;
          break;
        case 'returns':
          app.returnsSelectedCustomer = customer;
          break;
        case 'others':
          app.othersSelectedCustomer = customer;
          break;
        case 'manager':
          app.managerSelectedCustomer = customer;
          break;
        case 'debtor':
          app.debtor = customer;
          break;
        default:
          break;
      }
      app.dialogVisible = false;
    },
  },
};
</script>

