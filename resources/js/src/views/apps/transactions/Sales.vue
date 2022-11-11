<template>
  <vx-card>
    <div class="vx-row">
      <div class="vx-col w-full">
        <div class="flex items-end px-3">
          <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
          <span class="font-medium text-lg">Sales {{ sub_title }}</span>
        </div>
        <vs-divider />
      </div>

    </div>
    <filter-options @submitQuery="fetchSales" />
    <el-row v-loading="load" :gutter="10">
      <br>
      <el-col :md="8">
        <el-alert :closable="false" type="success"><h4>Total Sales: {{ currency }}{{ (total_sales.total_amount) ? total_sales.total_amount.toLocaleString() : 0 }}</h4></el-alert>
      </el-col>
      <el-col :md="16">
        <span class="pull-right">

          <el-select v-if="form.team_id !== ''" v-model="view_type" placeholder="Select View Type" @change="fetchSales(form)">
            <el-option
              value="invoice"
              label="View Invoice Summary Only"
            />
            <el-option
              value="product"
              label="View Product Details"
            />
          </el-select>
          <el-button
            v-if="view_type === 'invoice'"
            :loading="downloadLoading"
            round
            style="margin:0 0 20px 20px;"
            type="success"
            icon="el-icon-download"
            size="small"
            @click="handleDownload"
          >Export Excel</el-button>
          <el-button
            v-else
            :loading="downloadLoading"
            round
            style="margin:0 0 20px 20px;"
            type="success"
            icon="el-icon-download"
            size="small"
            @click="handleDownload2"
          >Export Excel</el-button>
        </span>
      </el-col>
      <v-client-table v-if="view_type === 'invoice'" v-model="sales" :columns="sales_columns" :options="sales_options">
        <div slot="child_row" slot-scope="props" style="background: #000">

          <div v-if="props.row.complain_status === 'pending'">
            <h4><el-alert :closable="false" type="error">Complaint: {{ props.row.complaints }}</el-alert></h4>
          </div>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th
                  v-if="checkPermission(['update-sales']) && props.row.complain_status === 'pending'">Edit</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Rate</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>

              <tr v-for="(detail, index) in props.row.details" :key="index">

                <td v-if="checkPermission(['update-sales']) && props.row.complain_status === 'pending'">
                  <el-popover
                    placement="right"
                    width="400"
                    trigger="click">
                    <table class="table table-bordered">
                      <tbody>
                        <tr>
                          <td>Old Quantity</td>
                          <td>{{ updateDetails.old_quantity }} {{ updateDetails.type }}</td>
                        </tr>
                        <tr>
                          <td>New Quantity</td>
                          <el-input v-model="updateDetails.new_quantity" type="number">
                            <template #append>
                              {{ updateDetails.type }}
                            </template>
                          </el-input>
                        </tr>
                        <tr>
                          <td>Rate</td>
                          <td>
                            <el-input v-model="updateDetails.rate" type="number">
                              <template #prepend>
                                {{ currency }}
                              </template>
                            </el-input>
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
                      :id="`update_sales_${detail.id}`"
                      type="warning"
                      circle
                      icon="el-icon-edit"
                      @click="setUpdateDetailsForm(detail)"
                    />
                  </el-popover>
                </td>
                <td>{{ detail.product }}</td>
                <td>{{ detail.quantity }} {{ detail.packaging }}</td>
                <td>{{ currency + Number(detail.rate).toLocaleString() }}</td>
                <td>{{ currency + Number(detail.amount).toLocaleString() }}</td>
              </tr>
              <tr v-if="checkPermission(['update-sales']) && props.row.complain_status == 'pending'">
                <td colspan="5">
                  <el-button
                    type="success"
                    round
                    @click="resolveComplaints(props.row)"
                  >Complaints Resolved</el-button>
                </td>
              </tr>
            </tbody>
          </table>

        </div>
        <div
          slot="amount_due"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.amount_due).toLocaleString() }}</div>
        <div
          slot="created_at"
          slot-scope="props"
        >{{ moment(props.row.created_at).format('lll') }}</div>
        <template slot="attachments" slot-scope="{row}">
          <el-button
            v-if="row.attachments.length > 0"
            type="primary"
            round
            @click="viewDetails(row)"
          >View</el-button>
        </template>
      </v-client-table>
      <v-client-table v-else v-model="sales" :columns="product_sales_columns" :options="product_salesoptions">
        <div
          slot="quantity"
          slot-scope="props"
        >{{ props.row.quantity + ' ' + props.row.packaging }}</div>
        <div
          slot="amount"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.amount).toLocaleString() }}</div>
        <div
          slot="rate"
          slot-scope="props"
          class="alert alert-success"
        >{{ currency + Number(props.row.rate).toLocaleString() }}</div>
        <!-- <div
          slot="main_rate"
          slot-scope="props"
          class="alert alert-info"
        >{{ currency + Number(props.row.main_rate).toLocaleString() }}</div>
        <div
          slot="main_amount"
          slot-scope="props"
          class="alert alert-info"
        >{{ currency + Number(props.row.main_amount).toLocaleString() }}</div>
        <div
          slot="balance"
          slot-scope="props"
          class="alert alert-danger"
        >{{ currency + Number(props.row.amount_due - props.row.amount_paid).toLocaleString() }}</div>
        <div
          slot="expiry_date"
          slot-scope="props"
        >{{ (props.row.expiry_date) ? moment(props.row.expiry_date).format('ll') : '' }}</div> -->
        <div
          slot="entry_date"
          slot-scope="props"
        >{{ moment(props.row.entry_date).format('lll') }}</div>
      </v-client-table>
    </el-row>
    <el-row :gutter="20">
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="form.page"
        :limit.sync="form.limit"
        @pagination="fetchSales(form)"
      />
    </el-row>
    <el-dialog
      :visible.sync="dialogVisible"
      title="Attached Document"
      width="50%">
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
import FilterOptions from '@/views/apps/reports/FilterOptions';
import checkPermission from '@/utils/permission'; // Permission checking
export default {
  components: { Pagination, FilterOptions },
  data() {
    return {
      dialogVisible: false,
      details: '',
      sales: [],
      reps: [],
      sales_columns: [
        'attachments',
        'customer.business_name',
        'invoice_no',
        'amount_due',
        // 'batch_no',
        // 'expiry_date',
        'staff.name', // field staff
        'created_at',
      ],
      sales_options: {
        headings: {
          'customer.business_name': 'Customer Name',
          invoice_no: 'Invoice No.',
          amount_due: 'Amount',
          delivery_status: 'Delivery Status',
          'staff.name': 'Field Staff',
          //   'rate': 'Sell Rate',
          //   'amount': 'Sell Amount',
          //   'main_amount': 'Original Amount',
          //   'main_rate': 'Original Rate',
          'created_at': 'Date',
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
        sortable: ['customer.business_name', 'invoice_no', 'created_at', 'staff.name,'],
        filterable: ['invoice_no', 'customer.business_name', 'created_at', 'staff.name'],
      },
      product_sales_columns: [
        // 'action',
        'transaction.customer.business_name',
        'transaction.invoice_no',
        'product',
        'quantity',
        // 'main_rate',
        // 'main_amount',
        'rate',
        'amount',
        // 'batch_no',
        // 'expiry_date',
        'name', // field staff
        'entry_date',
      ],
      product_salesoptions: {
        headings: {
          'transaction.customer.business_name': 'Customer Name',
          'transaction.invoice_no': 'Invoice No.',
          amount_due: 'Amount',
          amount_paid: 'Amount Paid',
          delivery_status: 'Delivery Status',
          'name': 'Field Staff',
          //   'rate': 'Sell Rate',
          //   'amount': 'Sell Amount',
          //   'main_amount': 'Original Amount',
          //   'main_rate': 'Original Rate',
          'entry_date': 'Date',
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
        sortable: ['transaction.invoice_no', 'product', 'transaction.customer.business_name', 'entry_date', 'name,'],
        filterable: ['transaction.invoice_no', 'product', 'transaction.customer.business_name', 'entry_date', 'name'],
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
        limit: 25,
        customer_id: 'all',
        rep_id: '',
        team_id: '',
      },
      view_type: 'invoice',
      sub_title: '',
      submitTitle: 'Fetch Report',
      panel: 'month',
      future: false,
      panels: ['range', 'week', 'month', 'quarter', 'year'],
      show_calendar: false,
      downloadLoading: false,
      customers: [],
      total_sales: {
        total_amount: 0,
      },
      updateDetails: {
        id: '',
        old_quantity: '',
        new_quantity: 0,
        rate: '',
        type: '',
      },
    };
  },
  created() {
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
      app.updateDetails.id = data.id;
      app.updateDetails.old_quantity = data.quantity;
      app.updateDetails.new_quantity = 0;
      app.updateDetails.rate = data.rate;
      app.updateDetails.type = data.packaging;
    },
    updateEntry() {
      const app = this;
      const updateSalesResource = new Resource('sales/update-details');
      const param = app.updateDetails;
      app.load = true;
      updateSalesResource.update(param.id, param)
        .then(() => {
          app.fetchSales(app.form);
          document.getElementById(`update_sales_${param.id}`).click();
        }).catch((e) => {
          app.load = false;
        });
    },
    resolveComplaints(sale) {
      const app = this;
      const message = 'Are you sure you have resolved all complaints about this entry? Click OK to confirm';
      if (confirm(message)) {
        const resolveComplainResource = new Resource('sales/resolve-complaints');
        app.load = true;
        resolveComplainResource.update(sale.id)
          .then(() => {
            app.fetchSales(app.form);
          }).catch((e) => {
            app.load = false;
          });
      }
    },
    fetchSales(param) {
      const app = this;
      app.sales = [];
      app.form = param;
      const { limit, page } = param;
      app.sales_options.perPage = limit;
      let salesResource = new Resource('sales/fetch-product-sales');
      if (app.view_type === 'invoice') {
        salesResource = new Resource('sales/fetch');
      }
      app.load = true;
      salesResource.list(param)
        .then(response => {
          app.sales = response.sales.data;
          app.total_sales = response.total_sales;
          app.currency = response.currency;
          app.sub_title = ' from ' + response.date_from + ' to ' + response.date_to;
          app.sales.forEach((element, index) => {
            element['index'] = (page - 1) * limit + index + 1;
          });
          this.total = response.sales.total;
          app.load = false;
        });
    },
    handleDownload() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Sales ' + this.sub_title, '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'INVOICE NUMBER',
          'AMOUNT',
          // 'BATCH NO.',
          // 'EXPIRY DATE',
          'FIELD STAFF',
          'DATE',
        ];
        const filterVal = [
          'customer.business_name',
          'invoice_no',
          'amount_due',
          // 'batch_no',
          // 'expiry_date',
          'staff.name', // field staff
          'created_at',
        ];
        const list = this.sales;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Sales',
          autoWidth: true,
          bookType: 'csv',
        });
        this.downloadLoading = false;
      });
    },
    handleDownload2() {
      this.downloadLoading = true;
      import('@/vendor/Export2Excel').then(excel => {
        const multiHeader = [['Product Sales ' + this.sub_title, '', '', '', '', '', '', '', '']];
        const tHeader = [
          'CUSTOMER',
          'INVOICE NUMBER',
          'PRODUCT',
          'QUANTITY',
          //   'ORIGINAL RATE',
          //   'ORIGINAL AMOUNT',
          'RATE',
          'AMOUNT',
          // 'BATCH NO.',
          // 'EXPIRY DATE',
          'FIELD STAFF',
          'DATE',
        ];
        const filterVal = [
        // 'action',
          'transaction.customer.business_name',
          'transaction.invoice_no',
          'product',
          'quantity',
          // 'main_rate',
          // 'main_amount',
          'rate',
          'amount',
          // 'batch_no',
          // 'expiry_date',
          'name', // field staff
          'entry_date',
        ];
        const list = this.sales;
        const data = this.formatJson(filterVal, list);
        excel.export_json_to_excel({
          multiHeader,
          header: tHeader,
          data,
          filename: 'Product Sales',
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
          if (j === 'created_at') {
            return moment(v['created_at']).format('lll');
          }
          if (j === 'customer.business_name') {
            return v['customer']['business_name'];
          }
          if (j === 'staff.name') {
            return v['staff']['name'];
          }
          if (j === 'invoice_no') {
            return v['invoice_no'];
          }
          if (j === 'quantity') {
            return v['quantity'] + ' ' + v['packaging'];
          }
          return v[j];
        }),
      );
    },
  },
};
</script>
