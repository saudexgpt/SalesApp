<template>
  <div v-loading="load" class="vx-row">
    <el-row :gutter="10">
      <el-col :span="12">
        <aside>
          Manage Duplicate Customers by following the instruction below:<br>
          <ul style="list-style-type: disc !important;">
            <li>Take note of the Customer Codes considered as duplicates. <br>For example, Customers with codes: <br><code>BEL-000001,BEL-002001,BEL-001901</code><br> may be considered duplicates.</li>
            <li>Fill the form accordingly as specified by the table header names.</li>
            <li>Paste the Duplicate Codes in the designated field as demonstrated by the example, separating them with a comma.</li>
            <li>Then paste the Customer Code that should stand as the Valid Customer.</li>
            <li>You can add multiple entries using the + button</li>
            <li>Leave no field empty.</li>
            <li>Then Submit when done</li>
          </ul>
          <div>
            <h4>Effect of the Action Above:</h4>
            All Transactions with the specified Duplicate Customers will be transferred to the Valid Customer, then ONLY the Duplicate Customers will be removed.
          </div>
        </aside>
      </el-col>
      <el-col :span="12">

        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Action</th>
              <th>Duplicate Customer Codes</th>
              <th>Valid Customer Code</th>
              <!-- <th>Attach File</th> -->
            </tr>
          </thead>
          <tbody>
            <tr v-for="(duplicate_entry, index) in duplicate_entries" :key="index">
              <td>
                <div class="demo-alignment">
                  <vs-button v-if="duplicate_entries.length > 1" radius color="danger" icon-pack="feather" icon="icon-trash" @click="removeDuplicateEntry(index)" />
                  <vs-button v-if="index + 1 === duplicate_entries.length" radius color="primary" icon-pack="feather" icon="icon-plus" @click="addDuplicateEntry(index)" />
                </div>
              </td>
              <td>
                <el-input
                  v-model="duplicate_entry.duplicate_customer_codes"
                  placeholder="BEL-000001,BEL-002001,BEL-001901"
                  type="textarea"
                />
              </td>
              <td>
                <el-input
                  v-model="duplicate_entry.customer_to_remain"
                  placeholder="BEL-000001"
                />
              </td>
            </tr>
            <tr v-if="fill_duplicate_fields_error">
              <td colspan="6">
                <label
                  class="label label-danger"
                >Please fill all empty fields before adding another row</label>
              </td>
            </tr>
            <tr v-if="!isDuplicateRowEmpty()" >
              <td colspan="6">
                <el-button round type="success" @click="submitDuplicateCustomer()">Submit</el-button>
              </td>
            </tr>
          </tbody>
        </table>
      </el-col>
    </el-row>
  </div>
</template>
<script>
import Resource from '@/api/resource';

export default {
  data() {
    return {
      duplicate_entries: [],
      fill_duplicate_fields_error: false,
      showSaveButton: true,
      load: false,
      rowIndex: '',
    };
  },
  created() {
    this.addDuplicateEntry();
  },
  methods: {
    isDuplicateRowEmpty() {
      const checkEmptyLines = this.duplicate_entries.filter(
        (detail) =>
          detail.duplicate_customer_codes === '' ||
          detail.customer_to_remain === ''
      );
      if (checkEmptyLines.length > 0) {
        return true;
      }
      return false;
    },
    addDuplicateEntry() {
      this.fill_duplicate_fields_error = false;

      if (this.isDuplicateRowEmpty()) {
        this.fill_duplicate_fields_error = true;
        // this.invoice_items[index].seleted_category = true;
        return;
      } else {
        this.duplicate_entries.push({
          duplicate_customer_codes: '',
          customer_to_remain: '',
        });
      }
    },
    removeDuplicateEntry(detailId) {
      this.fill_duplicate_fields_error = false;
      if (!this.duplicateBlockRemoval) {
        this.duplicate_entries.splice(detailId, 1);
      }
    },
    submitDuplicateCustomer() {
      const app = this;
      app.$confirm('Are you sure you want to remove these entries? They cannot be undone', 'Warning', {
        confirmButtonText: 'Yes Submit',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        app.load = true;
        if (app.duplicate_entries.length > 0) {
          const unsaved_orders = { unsaved_duplicate_entries: app.duplicate_entries };
          const submitDuplicateEntries = new Resource('customers/remove-duplicate');
          submitDuplicateEntries.store(unsaved_orders).then(response => {
            this.$message({
              type: 'success',
              message: 'Duplicates Removed',
            });
            app.duplicate_entries = response.unsaved_entry;
            app.addDuplicateEntry();
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
