<template>
  <vx-card v-loading="load_table">

    <vs-tabs>
      <vs-tab label="Main Inventory">
        <div class="tab-text">
          <br>
          <main-inventory :inventories="inventories" @update="updateStock" />
        </div>
      </vs-tab>
      <vs-tab label="Van Inventory">
        <div class="tab-text">
          <br>
          <van-inventory :van-inventories="sub_inventories" />
        </div>
      </vs-tab>
    </vs-tabs>

  </vx-card>
</template>

<script>
import MainInventory from './partials/MainInventory';
import VanInventory from './partials/VanInventory';
import Resource from '@/api/resource';
export default {
  name: 'MyInventory',
  components: { MainInventory, VanInventory },
  data() {
    return {
      inventories: [],
      sub_inventories: [],
      load_table: false,
    };
  },
  created() {
    this.fetchInventory();
  },
  methods: {

    fetchInventory() {
      this.load_table = true;
      const productsResource = new Resource('inventory/my-inventory');
      productsResource
        .list()
        .then((response) => {
          this.inventories = response.inventories;
          this.sub_inventories = response.sub_inventories;
          this.load_table = false;
        })
        .catch((error) => {
          console.log(error);
          this.load_table = false;
        });
    },
    updateStock(updatedStock) {
      this.inventories = updatedStock.inventories;
      this.sub_inventories = updatedStock.sub_inventories;
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
