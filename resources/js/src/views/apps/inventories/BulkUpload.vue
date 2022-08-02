<template>
  <div v-loading="load">
    <el-row :gutter="10" class="padded">
      <el-col :xs="24" :sm="24" :md="24">
        <aside>
          <el-button :loading="downloading" round class="filter-item" type="danger" icon="el-icon-download" @click="downloadFormat()">
            Download Format
          </el-button><br>
          <strong>Note:</strong> To upload bulk customers from excel file, kindly follow the instructions below: <br>
          <ol style="list-style-type: display;">
            <li>Make sure your file follows the header names as stated in the sample below</li>
            <li>Each column should contain only the information that the header name suggests</li>
            <li>When all fields are correctly filled, upload the file, preview and then click SUBMIT</li>
          </ol> <br>
          <!-- <label>Sample</label>
          <div class="table-responsive">

            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>BUSINESS_NAME</th>
                  <th>ADDRESS</th>
                  <th>AREA</th>
                  <th>LGA</th>
                  <th>CORDINATE</th>
                  <th>BUSINESS_TYPE</th>
                  <th>CONTACT_PERSON</th>
                  <th>CONTACT_NUMBER</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>ABC Pharmacy</td>
                  <td>48 Allen Avenue</td>
                  <td>Ikeja</td>
                  <td>Ikeja</td>
                  <td>6.2535245,3.6523254</td>
                  <td>Pharmacy</td>
                  <td>Onyema Ugochukwu</td>
                  <td>07012345678</td>
                </tr>
                <tr>
                  <td>XYZ Hospital</td>
                  <td>59 Tom Dick and Harry Street</td>
                  <td>Ikate</td>
                  <td>Surulere</td>
                  <td>6.2535555,3.6528954</td>
                  <td>Hospital</td>
                  <td>Felix Imoh</td>
                  <td>08152526396</td>
                </tr>
              </tbody>

            </table>
          </div> -->
        </aside>
      </el-col>
      <el-col :xs="24" :sm="24" :md="24">
        <upload-excel-component :on-success="handleSuccess" :before-upload="beforeUpload" />
      </el-col>
    </el-row>
    <!-- <el-row v-if="errors.length > 0" :gutter="5" class="padded">
      <div class="alert alert-danger">
        <span v-for="(error, error_index) in errors" :key="error_index">
          {{ error }}<br>
        </span>
      </div>
    </el-row> -->
    <div v-if="dialogVisible">
      <el-row :gutter="2" class="padded">
        <el-col :xs="24" :sm="6" :md="6">
          <el-button type="success" @click="addNewCustomer">
            <i class="el-icon-plus" />
            SUMBIT
          </el-button>
        </el-col>
      </el-row>
      <el-table :data="tableData" border highlight-current-row style="width: 100%;margin-top:20px;">
        <el-table-column v-for="item of tableHeader" :key="item" :prop="item" :label="item" />
      </el-table>
      <el-row :gutter="2" class="padded">
        <el-col :xs="24" :sm="6" :md="6">
          <el-button type="success" @click="addNewCustomer">
            <i class="el-icon-plus" />
            SUMBIT
          </el-button>
        </el-col>
      </el-row>
    </div>
    <!-- <el-dialog
      :title="preview_title"
      :visible.sync="dialogVisible"
      width="90%"
    >
      <el-table :data="tableData" border highlight-current-row style="width: 100%;margin-top:20px;">
        <el-table-column v-for="item of tableHeader" :key="item" :prop="item" :label="item" />
      </el-table>
    </el-dialog> -->
  </div>
</template>
<script>
// import UploadExcelComponent from '@/components/UploadExcel/index.vue';
import UploadExcelComponent from '@/components/excel/ImportExcel.vue';
import Resource from '@/api/resource';
const saveBulkCustomers = new Resource('inventory/store-bulk-inventory');
export default {
  components: { UploadExcelComponent },
  data() {
    return {
      pickerOptions: {
        disabledDate(date) {
          var d = new Date(); // today
          d.setDate(d.getDate()); // one year from now
          return date > d;
        },
      },
      dialogVisible: false,
      preview_title: '',
      load: false,
      fill_fields_error: false,
      tableData: [],
      tableHeader: [],
      selected_index: 0,
      error: [],
      downloading: false,
    };
  },
  methods: {
    beforeUpload(file) {
      const isLt1M = file.size / 1024 / 1024 < 2;

      if (isLt1M) {
        return true;
      }

      this.$message({
        message: 'Please do not upload files larger than 2m in size.',
        type: 'warning',
      });
      return false;
    },
    handleSuccess({ results, header }) {
      const app = this;
      app.tableData = results;
      app.tableHeader = header;
      app.preview_title = 'Customer Details to be uploaded';
      app.dialogVisible = true;
    },
    addNewCustomer() {
      const app = this;
      app.load = true;
      const param = { bulk_data: app.tableData };
      saveBulkCustomers
        .store(param)
        .then((response) => {
          app.tableData = [];
          if (response.message === 'success') {
            app.$message({
              message: 'Bulk Customers Uploaded Successfully!!!',
              type: 'success',
            });
            // app.tableData = response.unsaved_customers;
            // app.$router.push({ name: 'Customers' });
          } else {
            app.errors = response.error;
          }
          if (response.unsaved_customers.length > 0) {
            app.tableData = response.unsaved_customers;
            app.error = response.error;
          } else {
            app.$router.push({ name: 'ViewCustomer' });
          }
          app.load = false;
        })
        .catch((error) => {
          app.load = false;
          console.log(error.message);
        });
    },
    downloadFormat() {
      import('@/vendor/Export2Excel').then(excel => {
        const tHeader = ['ITEM_NAME', 'QUANTITY', 'EXPIRY_DATE', 'REP_ID'];
        const data = [];
        excel.export_json_to_excel({
          header: tHeader,
          data,
          filename: 'INVENTORY LIST',
        });
      });
    },
  },
};
</script>
