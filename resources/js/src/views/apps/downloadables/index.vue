<template>
  <el-card>
    <el-alert :closable="false" type="error"><h4>To download a report, set your query preferences before clicking on a download button</h4></el-alert>
    <hr>
    <filter-options @submitQuery="setParam" />
    <hr>
    <el-row>
      <el-col :span="24">
        <div>
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
            <el-input :value="`${baseUrl}/report/download/product-sales${strParams}`" class="form-control" readonly>
              <template #append>
                <el-button @click="copyToClipboard(`${baseUrl}/report/download/product-sales${strParams}`)">Copy API</el-button>
              </template>
            </el-input>

          </span>
          <hr>
        </div>
      </el-col>
      <el-col :span="24">
        <div>
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
            <el-input :value="`${baseUrl}/report/download/collections${strParams}`" class="form-control" readonly>
              <template #append>
                <el-button @click="copyToClipboard(`${baseUrl}/report/download/collections${strParams}`)">Copy API</el-button>
              </template>
            </el-input>

          </span>
          <hr>
        </div>
      </el-col>
      <el-col :span="24">
        <div>
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
            <el-input :value="`${baseUrl}/report/download/debts${strParams}`" class="form-control" readonly>
              <template #append>
                <el-button @click="copyToClipboard(`${baseUrl}/report/download/debts${strParams}`)">Copy API</el-button>
              </template>
            </el-input>

          </span>
          <hr>
        </div>
      </el-col>
      <el-col :span="24">
        <div>
          <span >
            <el-button
              :loading="downloadLoading"
              round
              style="margin:0 0 20px 20px;"
              type="default"
              icon="el-icon-download"
              size="large"
              @click="downloadReturns()"
            >Download Returns Report</el-button>

            <el-input :value="`${baseUrl}/report/download/returned-products${strParams}`" class="form-control" readonly>
              <template #append>
                <el-button @click="copyToClipboard(`${baseUrl}/report/download/returned-products${strParams}`)">Copy API</el-button>
              </template>
            </el-input>

          </span>
          <hr>
        </div>
      </el-col>
      <el-col :span="24">
        <div>
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
            <el-input :value="`${baseUrl}/report/download/visits${strParams}`" class="form-control" readonly>
              <template #append>
                <el-button @click="copyToClipboard(`${baseUrl}/report/download/visits${strParams}`)">Copy API</el-button>
              </template>
            </el-input>

          </span>
          <hr>
        </div>
      </el-col>
      <el-col :span="24">
        <div>
          <span >
            <el-button
              :loading="downloadLoading"
              round
              style="margin:0 0 20px 20px;"
              type="info"
              icon="el-icon-download"
              size="large"
              @click="downloadCustomers()"
            >Download Customers Report</el-button>
            <el-input :value="`${baseUrl}/report/download/customers${strParams}`" class="form-control" readonly>
              <template #append>
                <el-button @click="copyToClipboard(`${baseUrl}/report/download/customers${strParams}`)">Copy API</el-button>
              </template>
            </el-input>

          </span>
          <hr>
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
        'customer.customer_type.name',
        'purpose',
        'visited_by.name',
        'rep_coordinate',
        'proximity',
        // 'visit_type',
        'manager_coordinate',
        'manager_proximity',
        'created_at',
      ],
      unvisited_cust_columns: [
        'business_name',
        'address',
        'latitude',
        'longitude',
        'assigned_officer.name',
      ],
      unvisited_schedule_columns: [
        'customer.business_name',
        'customer.address',
        'day',
        'schedule_date',
        'rep.name',
        'scheduled_by.name',
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
      returns_columns: [
        'customer.business_name',
        'item.name',
        'quantity',
        // 'rate',
        // 'amount',
        'batch_no',
        'expiry_date',
        'reason',
        'rep.name', // field staff
        'created_at',
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
      strParams: '',
      downloadLoading: false,
    };
  },
  computed: {
    baseUrl() {
      return this.$store.getters.baseUrl;
    },
  },
  methods: {
    moment,
    setParam(param) {
      const app = this;
      app.form = param;
      app.setStrParam();
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
      const header = [
        'CUSTOMER',
        'TYPE',
        'PURPOSE',
        'REP',
        'REP COORDINATE',
        'REP-CUSTOMER PROXIMITY(m)',
        'MANAGER COORDINATE',
        'MANAGER-REP PROXIMITY(m)',
        'DATE',
      ];
      //   const header2 = [
      //     'CUSTOMER',
      //     'ADDRESS',
      //     'LATITUDE',
      //     'LONGITUDE',
      //     'REP',
      //   ];
      //   const header3 = [
      //     'CUSTOMER',
      //     'ADDRESS',
      //     'DAY',
      //     'SCHEDULE DATE',
      //     'REP',
      //     'SCHEDULED BY',
      //   ];
      app.downloadLoading = true;
      salesResource.list(param)
        .then(response => {
          const sub_title = 'Visits from ' + response.date_from + ' to ' + response.date_to;

          app.handleDownload(response.visits, app.visits_columns, header, sub_title);
        });
    //   const visitsResource = new Resource('visits/customer-visit-stat');
    //   visitsResource.list(param)
    //     .then(response => {
    //       app.handleDownload(response.scheduled_visits, app.visits_columns, header, 'Scheduled Visits Report');

    //       app.handleDownload(response.unscheduled_visits, app.visits_columns, header, 'Unscheduled Visits Report');

    //       app.handleDownload(response.unvisited_schedule, app.unvisited_schedule_columns, header3, 'Unvisited Schedule Report');

    //       app.handleDownload(response.company_customers_visits, app.visits_columns, header, 'Company\'s Customers Visits Report');

    //       app.handleDownload(response.reps_customers_visits, app.reps_customers_visits, header, 'Rep\'s Customers Visits Report');

    //       app.handleDownload(response.notvisited_company_customers, app.unvisited_cust_columns, header2, 'Unvisited Company\'s Customers Report');

    //       app.handleDownload(response.notvisited_rep_customers, app.unvisited_cust_columns, header2, 'Unvisited Rep\'s Customers Report');

    //       app.downloadLoading = false;
    //     });
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
    downloadReturns() {
      const app = this;
      //   if (app.emptyDateRange()) {
      //     app.$alert('Kindly pick a date range to continue');
      //     return;
      //   }
      const param = app.form;
      param.paginate_option = 'all';
      param.verify_type = 'all';
      const salesResource = new Resource('reports/fetch-returned-products');
      app.downloadLoading = true;
      salesResource.list(param)
        .then(response => {
          const sub_title = 'Products Returned List';
          const header = [
            'CUSTOMER',
            'PRODUCT',
            'QUANTITY',
            // 'RATE',
            // 'AMOUNT',
            'BATCH NO.',
            'EXPIRY DATE',
            'REASON',
            'FIELD STAFF',
            'CREATED AT',
          ];
          app.handleDownload(response.returns, app.returns_columns, header, sub_title);
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
            moment(v['expiry_date']).format('DD/MM/YYYY');
          }
          if (j === 'transaction.created_at') {
            return (v['transaction']) ? moment(v['transaction']['created_at']).format('DD/MM/YYYY') : '';
          }
          if (j === 'transaction.customer.business_name') {
            return v['transaction']['customer']['business_name'];
          }
          if (j === 'transaction.invoice_no') {
            return v['transaction']['invoice_no'];
          }
          if (j === 'quantity') {
            if (v['packaging']) {
              return v['quantity'] + ' ' + v['packaging'];
            } else {
              return v['quantity'] + ' ' + v['item']['package_type'];
            }
          }
          if (j === 'updated_at') {
            return (v['confirmer']) ? moment(v['updated_at']).format('DD/MM/YYYY') : 'Pending';
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
            return moment(v['created_at']).format('DD/MM/YYYY');
          }
          if (j === 'date') {
            return moment(v['created_at']).format('DD/MM/YYYY');
          }
          if (j === 'contact.name') {
            return (v['contact']) ? v['contact']['name'] : '';
          }
          if (j === 'visited_by.name') {
            return (v['visited_by']) ? v['visited_by']['name'] : '';
          }
          if (j === 'date_verified') {
            return (v['date_verified']) ? moment(v['date_verified']).format('DD/MM/YYYY') : 'Not Verified';
          }
          if (j === 'last_visit') {
            return (v['last_visited']) ? moment(v['last_visited']['visit_date']).format('DD/MM/YYYY') : 'Not Visited';
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
          if (j === 'rep.name') {
            return v['rep']['name'];
          }
          if (j === 'scheduled_by.name') {
            return v['scheduled_by']['name'];
          }
          if (j === 'item.name') {
            return v['item']['name'];
          }
          if (j === 'customer.customer_type.name') {
            return (v['customer']) ? v['customer']['customer_type']['name'] : '';
          }
          if (j === 'rep_coordinate') {
            return (v['rep_latitude']) ? v['rep_latitude'] + ',' + v['rep_longitude'] : '';
          }
          if (j === 'manager_coordinate') {
            return (v['manager_latitude']) ? v['manager_latitude'] + ',' + v['manager_longitude'] : '';
          }
          return v[j];
        }),
      );
    },
    setStrParam(){
      const app = this;
      app.strParams = `?from=${app.form.from}&to=${app.form.to}&rep_id=${app.form.rep_id}&team_id=${app.form.team_id}`;
    },
    copyToClipboard(text) {
      navigator.clipboard.writeText(text);
      this.$message('API link copied to clipboard');
    },
  },
};
</script>

