<template>
  <div>
    <vx-card>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th />
            <th>Choose Rep</th>
            <th>Lower Limit</th>
            <th>Upper Limit</th>
            <th>Range</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(inventory_item, index) in invoice_booklets" :key="index">
            <td>
              <span>
                <vs-button radius color="danger" type="filled" icon-pack="feather" icon="icon-trash-2" @click="removeLine(index)"/>
                <vs-button v-if="index + 1 === invoice_booklets.length" radius color="success" type="filled" icon-pack="feather" icon="icon-plus" @click="addLine(index)"/>
              </span>
            </td>
            <td>
              <el-select
                v-model="inventory_item.rep_id"
                placeholder="Select Rep"
                filterable
                class="span"
                style="width: 100%"
              >
                <el-option
                  v-for="(staff, staff_index) in reps"
                  :key="staff_index"
                  :value="staff.id"
                  :label="staff.name"
                />
              </el-select>
            </td>
            <td>
              <el-input
                v-model="inventory_item.lower_limit"
                type="number"
                outline
                placeholder="001000"
                min="1"/>
            </td>
            <td>
              <el-input
                v-model="inventory_item.upper_limit"
                type="number"
                outline
                placeholder="001200"
                min="1"/>
            </td>
            <td>
              <strong>{{ (inventory_item.lower_limit) ? inventory_item.lower_limit : 'Lower' }} - {{ (inventory_item.upper_limit) ? inventory_item.upper_limit : 'Upper' }}</strong>
            </td>
          </tr>
          <tr v-if="fill_fields_error">
            <td colspan="5">
              <label
                class="label label-danger"
              >Please fill all empty fields before adding another row</label>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="5">
              <span class="pull-right">
                <vs-button color="success" type="filled" @click="submitBooklet()">Submit</vs-button>
              </span>
            </th>
          </tr>
        </tfoot>
      </table>
    </vx-card>
  </div>
</template>

<script>
import Resource from '@/api/resource';
export default {
  props: {
    reps: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      sales_reps: [],
      products: [],
      invoice_booklets: [],
      fill_fields_error: false,
    };
  },
  created() {
    this.addLine();
  },
  methods: {
    addLine() {
      this.fill_fields_error = false;

      const checkEmptyLines = this.invoice_booklets.filter(
        (detail) =>
          detail.lower_limit === '' ||
          detail.rep_id === '' ||
          detail.upper_limit === ''
      );

      if (checkEmptyLines.length >= 1 && this.invoice_booklets.length > 0) {
        this.fill_fields_error = true;
        // this.invoice_booklets[index].seleted_category = true;
        return;
      } else {
        // if (this.invoice_booklets.length > 0)
        //     this.invoice_booklets[index].grade = '';

        this.invoice_booklets.push({
          lower_limit: '',
          upper_limit: '',
          rep_id: '',
        });
      }
    },
    removeLine(detailId) {
      this.fill_fields_error = false;
      if (!this.blockRemoval) {
        this.invoice_booklets.splice(detailId, 1);
      }
    },
    submitBooklet() {
      const app = this;
      var form = { booklets: app.invoice_booklets };
      this.$vs.loading();

      const createBooklet = new Resource('invoice-booklets/store');
      createBooklet
        .store(form)
        .then((response) => {
          app.$message({
            message: 'Booklet Created Successfully!!!',
            type: 'success',
          });
          app.invoice_booklets = [];
          app.addLine();
          this.$vs.loading.close();
        })
        .catch((error) => {
          this.$vs.loading.close();
          console.log(error.message);
        });
    },
  },
};
</script>
<style>
.vs-con-input {
    margin-top: 20px !important ;
}
.dialog-footer {
    background: #f0f0f0;
    padding: 10px;
    margin-top: 20px !important ;
    position: relative;
}
</style>
