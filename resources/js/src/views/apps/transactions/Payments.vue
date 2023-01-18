<template>

  <vx-card>
    <div class="vx-row">
      <div class="vx-col lg:w-3/4 w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="CreditCardIcon" class="mr-2" />
          <span class="font-medium text-lg">Collections {{ sub_title }}</span>
        </div>
        <vs-divider />
      </div>
      <div class="vx-col lg:w-1/4 w-full">
        <div class="flex items-end px-3">
          <span class="pull-right">
            <el-button
              :loading="downloadLoading"
              round
              style="margin:0 0 20px 20px;"
              type="success"
              icon="el-icon-download"
              size="small"
              @click="handleDownload"
            >Export Excel</el-button>
          </span>
        </div>
      </div>
    </div>
    <filter-options @submitQuery="fetchPayments" />
    <el-row v-loading="load" :gutter="10">
      <br>
      <el-col :md="8">
        <el-alert :closable="false" type="success"><h4>Total Collections: {{ currency }}{{ (total_collections.total_amount) ? total_collections.total_amount.toLocaleString() : 0 }}</h4></el-alert>
      </el-col>
      <v-client-table v-model="payments" :columns="payments_columns" :options="payments_options">
        <div
          slot="confirmer.name"
          slot-scope="{row}"
        >{{ (row.confirmer) ? row.confirmer.name : 'Not Confirmed' }}</div>
        <div
          slot="total_amount"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.amount).toLocaleString() }}</div>
        <div
          slot="payment_date"
          slot-scope="props"
        >{{ moment(props.row.payment_date).format('ll') }}</div>
        <div
          slot="created_at"
          slot-scope="props"
        >{{ moment(props.row.created_at).format('lll') }}</div>
        <div
          slot="updated_at"
          slot-scope="{row}"
        >{{ (row.confirmer) ? moment(row.updated_at).format('lll') : 'Pending' }}</div>
        <div
          slot="action"
          slot-scope="scope"
        >
          <!-- <el-button v-if="props.row.confirmer === null && checkPermission(['confirm-payments'])" round type="success" size="small" @click="confirmPayment(props.index, props.row)">Confirm</el-button> -->
          <el-popover
            v-if="checkPermission(['update-payments']) && scope.row.complain_status === 'pending'"
            placement="right"
            width="400"
            trigger="click">
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <td>Old Amount</td>
                  <td>{{ currency }} {{ updateCollections.old_amount }}</td>
                </tr>
                <tr>
                  <td>New Amount</td>
                  <td>
                    <el-input v-model="updateCollections.new_amount" type="number">
                      <template #prepend>
                        {{ currency }}
                      </template>
                    </el-input>
                  </td>
                </tr>
                <tr>
                  <td>New Amount</td>
                  <td>
                    <el-select
                      v-model="updateCollections.payment_type"
                      style="width: 100%"
                    >
                      <el-option value="Cash" label="Cash" />
                      <el-option value="Cheque" label="Cheque" />
                      <el-option value="Bank Deposit/Transfer" label="Bank Deposit/Transfer/POS" />
                    </el-select>
                  </td>
                </tr>

                <tr>
                  <td colspan="2">
                    <el-button
                      type="primary"
                      round
                      @click="updateEntry()"
                    >
                      Submit
                    </el-button>
                  </td>
                </tr>
              </tbody>
            </table>
            <el-button
              slot="reference"
              :id="`update_payment_${scope.row.id}`"
              type="warning"
              circle
              icon="el-icon-edit"
              @click="setUpdateDetailsForm(scope.row)"
            />
          </el-popover>
        </div>
        <template slot="attachments" slot-scope="{row}">
          <el-button
            v-if="row.attachments.length > 0"
            type="primary"
            round
            @click="viewDetails(row)"
          >View</el-button>
        </template>
      </v-client-table>
    </el-row>
    <el-row :gutter="20">
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="form.page"
        :limit.sync="form.limit"
        @pagination="fetchPayments(form)"
      />
    </el-row>
    <el-dialog
      :visible.sync="dialogVisible"
      title="Attached Document"
      width="50%">
      <div v-if="details.complain_status === 'pending'">
        <h4><el-alert :closable="false" type="error">Complaint: {{ details.complaints }}</el-alert></h4>
      </div>
      <el-carousel indicator-position="outside">
        <el-carousel-item v-for="(attachment, index) in details.attachments" :key="index" style="overflow: auto">
          <img :src="`/storage/${attachment.link}`">
        </el-carousel-item>
      </el-carousel>
    </el-dialog>
  </vx-card>
</template>
<script>
import moment from 'moment';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
import checkPermission from '@/utils/permission'; // Permission checking
import FilterOptions from '@/views/apps/reports/FilterOptions';
export default {
  components: { Pagination, FilterOptions },
  data() {
    return {
      dialogVisible: false,
      details: '',
      payments: [],
      payments_columns: [
        'action',
        'id',
        'customer.business_name',
        'slip_no',
        'amount',
        'payment_type',
        'updated_at',
        'staff.name',
        'payment_date',
        'confirmer.name',
        // 'attachments',
        // 'action',
      ],

      payments_options: {
        headings: {
          'customer.business_name': 'Customer',
          'confirmer.name': 'Confirmed By',
          updated_at: 'Confirmed at',
          payment_date: 'Date',
          'staff.name': 'Field Staff',
        },
        pagination: {
          dropdown: true,
          chunk: 100,
        },
        perPage: 100,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['created_at'],
        filterable: ['customer.business_name'],
      },
      load: false,
      total: 0,
      currency: '',
      form: {
        from: '',
        to: '',
        panel: '',
        status: 'pending',
        page: 1,
        limit: 10,
        customer_id: 'all',
        rep_id: '',
      },
      sub_title: '',
      submitTitle: 'Fetch Report',
      panel: 'month',
      future: false,
      panels: ['range', 'week', 'month', 'quarter', 'year'],
      show_calendar: false,
      downloadLoading: false,
      customers: [],
      reps: [],
      total_collections: {
        total_amount: 0,
      },
      updateCollections: {
        id: '',
        old_amount: '',
        new_amount: 0,
        payment_type: '',
      },
    };
  },
  created() {
    // this.fetchSalesReps();
    // this.fetchCustomers();
    // this.fetchPayments();
  },
  methods: {
    moment,
    checkPermission,
    viewDetails(details){
      this.details = details;
      this.dialogVisible = true;
    },
    setUpdateDetailsForm(data) {
      const app = this;
      app.updateCollections.id = data.id;
      app.updateCollections.old_amount = data.amount;
      app.updateCollections.new_amount = 0;
      app.updateCollections.payment_type = data.payment_type;
    },
    updateEntry() {
      const app = this;
      const updateSalesResource = new Resource('payments/update-details');
      const param = app.updateCollections;
      app.load = true;
      updateSalesResource.update(param.id, param)
        .then(() => {
          app.fetchPayments(app.form);
          document.getElementById(`update_payment_${param.id}`).click();
        }).catch((e) => {
          app.load = false;
        });
    },
    fetchPayments(param) {
      const app = this;
      app.form = param;
      const { limit, page } = app.form;
      app.payments_options.perPage = limit;
      const paymentsResource = new Resource('payments');
      app.load = true;
      paymentsResource.list(param)
        .then(response => {
          app.payments = response.payments.data;
          app.total_collections = response.total_collections;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          this.payments.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.payments.total;
          app.load = false;
        });
    },
    confirmPayment(index, row) {
      const app = this;
      app.$confirm('Are you sure you want to confirm this payment? It cannot be undone', 'Warning', {
        confirmButtonText: 'Yes Confirm',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        const confirmPaymentsResource = new Resource('payments/confirm');
        const param = app.form;
        app.load = true;
        confirmPaymentsResource.update(row.id, param)
          .then(response => {
            app.payments[index - 1].confirmer = response.payment.confirmer;
            app.payments[index - 1].updated_at = response.payment.updated_at;
            app.load = false;
          });
      }).catch(() => {});
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Collections ' + this.sub_title, '', '', '', '', '', '', '', '', '']];
        const tHeader = [
          'ID',
          'CUSTOMER',
          'SLIP NO.',
          'AMOUNT',
          'PAYMENT TYPE',
          'FIELD STAFF',
          'PAYMENT DATE',
          'CONFIRMED BY',
          'CONFIRMED AT',
        ];
        const filterVal = [
          'id',
          'customer.business_name',
          'slip_no',
          'amount',
          'payment_type',
          'staff.name',
          'payment_date',
          'confirmer.name',
          'updated_at',
        // 'action',
        ];
        const list = this.payments;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Collections',
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    formatJson(filterVal, jsonData) {
      return jsonData.map(v =>
        filterVal.map(j => {
          if (j === 'updated_at') {
            return (v[j]) ? moment(v['updated_at']).format('lll') : '';
          }
          if (j === 'customer.business_name') {
            return v['customer']['business_name'];
          }
          if (j === 'confirmer.name') {
            return (v['confirmer']) ? v['confirmer']['name'] : '';
          }
          if (j === 'staff.name') {
            return (v['staff']) ? v['staff']['name'] : '';
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
