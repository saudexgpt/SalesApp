<template>
  <el-card>
    <el-alert :closable="false" type="error"><h4>To download a report, set your query preferences before clicking on a download button</h4></el-alert>
    <hr>
    <filter-options @submitQuery="setParam" />
    <hr>
    <el-row>
      <el-col :span="6">
        <div class="flex items-end px-3">
          <span >
            <el-button
              :loading="downloadLoading"
              round
              style="margin:0 0 20px 20px;"
              type="primary"
              icon="el-icon-download"
              size="large"
              @click="downloadSales()"
            >Download Sales Report</el-button>
          </span>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="flex items-end px-3">
          <span >
            <el-button
              :loading="downloadLoading"
              round
              style="margin:0 0 20px 20px;"
              type="success"
              icon="el-icon-download"
              size="large"
              @click="downloadCollections()"
            >Download Collections Report</el-button>
          </span>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="flex items-end px-3">
          <span >
            <el-button
              :loading="downloadLoading"
              round
              style="margin:0 0 20px 20px;"
              type="danger"
              icon="el-icon-download"
              size="large"
              @click="downloadDebts()"
            >Download Debts Report</el-button>
          </span>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="flex items-end px-3">
          <span >
            <el-button
              :loading="downloadLoading"
              round
              style="margin:0 0 20px 20px;"
              type="warning"
              icon="el-icon-download"
              size="large"
              @click="downloadVisits()"
            >Download Visits Report</el-button>
          </span>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="flex items-end px-3">
          <span >
            <el-button
              :loading="downloadLoading"
              round
              style="margin:0 0 20px 20px;"
              type="default"
              icon="el-icon-download"
              size="large"
              @click="downloadCustomers()"
            >Download Customers Report</el-button>
          </span>
        </div>
      </el-col>
    </el-row>
  </el-card>
</template>

<script>
import moment from 'moment';
import Resource from '@/api/resource';
import FilterOptions from '@/views/apps/reports/FilterOptions';
export default {
  name: 'Downloadables',
  components: { FilterOptions },
  props: {
    canAddNew: {
      type: Boolean,
      default: () => true,
    },
  },
  data() {
    return {
      sales_columns: [
        'transaction.customer.business_name',
        'transaction.invoice_no',
        'product',
        'quantity',
        'rate',
        'amount',
        'batch_no',
        'expiry_date',
        'name', // field staff
        'transaction.created_at',
      ],
      payments_columns: [
        'customer.business_name',
        'confirmer.name',
        'total_amount',
        'payment_date',
        'payment_type',
        'updated_at',
        'customer.assigned_officer.name',
      ],
      debts_columns: [
        'customer.business_name',
        'total_amount_due',
        'total_amount_paid',
        'balance',
        'relating_officer',
        'date',
        'age',
      ],
      visits_columns: [
        'customer.business_name',
        'visit_type',
        'proximity',
        'next_appointment_date',
        'contact.name',
        'purpose',
        'visited_by.name',
        'visit_duration',
        'created_at',
      ],
      customers_columns: [
        'business_name',
        'customer_type.name',
        'last_visit',
        'latitude',
        'longitude',
        'address',
        'latitude2',
        'longitude2',
        'address2',
        'latitude3',
        'longitude3',
        'address3',
        'registrar.name',
        'assigned_officer.name',
        'created_at',
        'date_verified',
      ],
      form: {
        from: '',
        to: '',
        panel: '',
        status: 'pending',
        page: 1,
        limit: 25,
        customer_id: 'all',
        rep_id: '',
      },
      downloadLoading: false,
    };
  },
  methods: {
    moment,
    setParam(param) {
      const app = this;
      app.form = param;
    },
    emptyDateRange() {
      if (this.form.from === '' || this.form.to === '') {
        return true;
      }
      return false;
    },
    downloadSales() {
      const app = this;
      if (app.emptyDateRange()) {
        app.$alert('Kindly pick a date range to continue');
        return;
      }
      const param = app.form;
      param.paginate_option = 'all';
      const salesResource = new Resource('sales/fetch-product-sales');
      app.downloadLoading = true;
      salesResource.list(param)
        .then(response => {
          const sub_title = 'Product Sales from ' + response.date_from + ' to ' + response.date_to;
          const header = [
            'CUSTOMER',
            'INVOICE NUMBER',
            'PRODUCT',
            'QUANTITY',
            'RATE',
            'AMOUNT',
            'BATCH NO.',
            'EXPIRY DATE',
            'FIELD STAFF',
            'DATE',
          ];
          app.handleDownload(response.sales, app.sales_columns, header, sub_title);
          app.downloadLoading = false;
        });
    },
    downloadCollections() {
      const app = this;
      if (app.emptyDateRange()) {
        app.$alert('Kindly pick a date range to continue');
        return;
      }
      const param = app.form;
      param.paginate_option = 'all';
      const salesResource = new Resource('payments');
      app.downloadLoading = true;
      salesResource.list(param)
        .then(response => {
          const sub_title = 'Collections from ' + response.date_from + ' to ' + response.date_to;
          const header = [
            'CUSTOMER',
            'CONFIRMED BY',
            'AMOUNT',
            'PAYMENT DATE',
            'PAYMENT TYPE',
            'CONFIRMED AT',
            'RELATING OFFICER',
          ];
          app.handleDownload(response.payments, app.payments_columns, header, sub_title);
          app.downloadLoading = false;
        });
    },
    downloadDebts() {
      const app = this;
      if (app.emptyDateRange()) {
        app.$alert('Kindly pick a date range to continue');
        return;
      }
      const param = app.form;
      param.paginate_option = 'all';
      const salesResource = new Resource('sales/fetch-debts');
      app.downloadLoading = true;
      salesResource.list(param)
        .then(response => {
          const sub_title = 'Customer Debts from ' + response.date_from + ' to ' + response.date_to;
          const header = [
            'CUSTOMER',
            'AMOUNT',
            'AMOUNT PAID',
            'BALANCE',
            'RELATING OFFICER',
            'DATE',
            'AGE',
          ];
          app.handleDownload(response.debts, app.debts_columns, header, sub_title);
          app.downloadLoading = false;
        });
    },
    downloadVisits() {
      const app = this;
      if (app.emptyDateRange()) {
        app.$alert('Kindly pick a date range to continue');
        return;
      }
      const param = app.form;
      param.paginate_option = 'all';
      const salesResource = new Resource('visits/fetch-general-visits');
      app.downloadLoading = true;
      salesResource.list(param)
        .then(response => {
          const sub_title = 'Visits from ' + response.date_from + ' to ' + response.date_to;
          const header = [
            'CUSTOMER',
            'VISIT TYPE',
            'PROXIMITY (M)',
            'FOLLOW-UP SCHEDULE',
            'CONTACTED PERSONNEL',
            'PURPOSE',
            'RELATING OFFICER',
            'VISIT DURATION',
            'CREATED AT',
          ];
          app.handleDownload(response.visits, app.visits_columns, header, sub_title);
          app.downloadLoading = false;
        });
    },
    downloadCustomers() {
      const app = this;
      //   if (app.emptyDateRange()) {
      //     app.$alert('Kindly pick a date range to continue');
      //     return;
      //   }
      const param = app.form;
      param.paginate_option = 'all';
      param.verify_type = 'all';
      const salesResource = new Resource('customers');
      app.downloadLoading = true;
      salesResource.list(param)
        .then(response => {
          const sub_title = 'Customers List';
          const header = [
            'BUSINESS NAME',
            'TYPE',
            'LAST VISIT',
            'LATITUDE 1',
            'LONGITUDE 1',
            'ADDRESS 1',
            'LATITUDE 2',
            'LONGITUDE 2',
            'ADDRESS 2',
            'LATITUDE 3',
            'LONGITUDE 3',
            'ADDRESS 3',
            'REGISTERED BY',
            'FIELD STAFF',
            'DATE REGISTERED',
            'LAST VERIFIED DATE',
          ];
          app.handleDownload(response.customers, app.customers_columns, header, sub_title);
          app.downloadLoading = false;
        });
    },
    handleDownload(dataList, columns, tHeader, title) {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [[title, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']];
        const filterVal = columns;
        const list = dataList;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: title,
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v =>
        filterVal.map(j => {
          if (j === 'expiry_date') {
            moment(v['expiry_date']).format('lll');
          }
          if (j === 'transaction.created_at') {
            return (v['transaction']) ? moment(v['transaction']['created_at']).format('lll') : '';
          }
          if (j === 'transaction.customer.business_name') {
            return v['transaction']['customer']['business_name'];
          }
          if (j === 'transaction.invoice_no') {
            return v['transaction']['invoice_no'];
          }
          if (j === 'quantity') {
            return v['quantity'] + ' ' + v['packaging'];
          }
          if (j === 'updated_at') {
            return (v['confirmer']) ? moment(v['updated_at']).format('lll') : 'Pending';
          }
          if (j === 'customer.business_name') {
            return (v['customer']) ? v['customer']['business_name'] : '';
          }
          if (j === 'confirmer.name') {
            return (v['confirmer']) ? v['confirmer']['name'] : 'Not Confirmed';
          }
          if (j === 'customer.assigned_officer.name') {
            return (v['customer']) ? v['customer']['assigned_officer']['name'] : '';
          }
          if (j === 'assigned_officer.name') {
            return (v['assigned_officer']) ? v['assigned_officer']['name'] : '';
          }
          if (j === 'customer_type.name') {
            return (v['customer_type']) ? v['customer_type']['name'] : '';
          }
          if (j === 'relating_officer') {
            return (v['customer']['assigned_officer']) ? v['customer']['assigned_officer']['name'] : '';
          }
          if (j === 'balance') {
            return v['total_amount_due'] - v['total_amount_paid'];
          }
          if (j === 'age') {
            return moment(v['created_at']).fromNow();
          }
          if (j === 'created_at') {
            return moment(v['created_at']).format('lll');
          }
          if (j === 'date') {
            return moment(v['created_at']).format('lll');
          }
          if (j === 'contact.name') {
            return (v['contact']) ? v['contact']['name'] : '';
          }
          if (j === 'visited_by.name') {
            return (v['visited_by']) ? v['visited_by']['name'] : '';
          }
          if (j === 'date_verified') {
            return (v['date_verified']) ? moment(v['date_verified']).format('lll') : 'Not Verified';
          }
          if (j === 'last_visit') {
            return (v['visits'].length > 0) ? moment(v['visits'][0]['visit_date']).format('ll') : 'Not Visited';
          }
          if (j === 'customer_type.name') {
            if (v['customer_type'] !== null) {
              return v['customer_type']['name'];
            }
          }
          if (j === 'registrar.name') {
            if (v['registrar'] !== null) {
              return v['registrar']['name'];
            }
          }
          if (j === 'assigned_officer.name') {
            if (v['assigned_officer'] !== null) {
              return v['assigned_officer']['name'];
            }
          }
          return v[j];
        }),
      );
    },
  },
};
</script>

