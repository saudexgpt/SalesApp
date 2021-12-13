<!-- =========================================================================================
    File Name: Login.vue
    Description: Login Page
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div id="page-login" class="h-screen flex w-full bg-img vx-row no-gutter items-center justify-center">
    <div class="vx-col sm:w-1/2 md:w-1/2 lg:w-3/4 xl:w-3/5 sm:m-0 m-4">
      <vx-card>
        <div slot="no-body" class="full-page-bg-color">

          <div class="vx-row no-gutter justify-center items-center">

            <div class="vx-col hidden sm:hidden md:hidden lg:block lg:w-1/2 mx-auto self-center">
              <img src="@assets/images/pages/sales.png" alt="Login" class="mx-auto" >
            </div>

            <div class="vx-col sm:w-full md:w-full lg:w-1/2 mx-auto self-center d-theme-dark-bg">
              <div class="px-8 pt-8 login-tabs-container">

                <div class="vx-card__title mb-4" align="center">
                  <img src="@assets/images/logo/logo.png" alt="logo" width="100" class="mx-auto" >
                </div>

                <div>
                  <vs-input
                    v-model="email"
                    data-vv-validate-on="blur"
                    name="email"
                    icon-no-border
                    icon="icon icon-user"
                    icon-pack="feather"
                    label-placeholder="Email"
                    class="w-full"
                  />
                  <!-- <span class="text-danger text-sm">{{ errors.first('email') }}</span> -->

                  <vs-input
                    v-model="password"
                    data-vv-validate-on="blur"
                    type="password"
                    name="password"
                    icon-no-border
                    icon="icon icon-lock"
                    icon-pack="feather"
                    label-placeholder="Password"
                    class="w-full mt-6"
                  />
                  <!-- <span class="text-danger text-sm">{{ errors.first('password') }}</span> -->

                  <div class="flex flex-wrap justify-between my-5">
                    <!-- <vs-checkbox v-model="checkbox_remember_me" class="mb-3">Remember Me</vs-checkbox>
      <router-link to="/forgot-password">Forgot Password?</router-link> -->
                  </div>
                  <div class="flex flex-wrap justify-between mb-3">

                    <vs-button @click="login">Login</vs-button>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </vx-card>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      email: '',
      password: '',
      checkbox_remember_me: false,
      redirect: undefined,
    };
  },
  watch: {
    $route: {
      handler(route) {
        this.redirect = route.query && route.query.to;
      },
      immediate: true,
    },
  },
  methods: {
    isLoggedIn() {
      // If user is already logged in notify
      if (this.$store.state.user.token) {
        // Close animation if passed as payload
        // this.$vs.loading.close()
        this.$vs.notify({
          title: 'Login Attempt',
          text: 'You are already logged in!',
          iconPack: 'feather',
          icon: 'icon-alert-circle',
          color: 'warning',
        });
        this.$router.push('/').catch(() => {});
        return true;
      }
      return false;
    },
    login() {
      if (this.isLoggedIn()) {
        return;
      }

      // Loading
      this.$vs.loading();

      const payload = {
        userDetails: {
          email: this.email,
          password: this.password,
          remember_me: this.checkbox_remember_me,
        },
      };
      this.$store
        .dispatch('user/login', payload.userDetails)
        .then(() => {
          this.$vs.notify({
            title: 'Login Success',
            text: 'Welcome',
            color: 'success',
            position: 'top-right',
            icon: 'check_box',
            time: 5000,
          });
          // we load the browser this once
          // window.location = '/dashboard';
          this.$router.replace({ path: '/dashboard' }).catch(() => {});
          this.$vs.loading.close();
        })
        .catch((error) => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: error.response.statusText,
            text: error.response.data.message,
            color: 'danger',
            position: 'top-right',
            icon: 'verified_user',
            time: 5000,
          });
          // console.log(error.response)
        });

      //   this.$store.dispatch('auth/login', payload)
      //     .then(() => { this.$vs.loading.close() })
      //     .catch(error => {
      //       this.$vs.loading.close()
      //       this.$vs.notify({
      //         title: 'Error',
      //         text: error.message,
      //         iconPack: 'feather',
      //         icon: 'icon-alert-circle',
      //         color: 'danger'
      //       })
      //     })
    },
  },
};
</script>

<style lang="scss">
.login-tabs-container {
  min-height: 505px;

  .con-tab {
    padding-bottom: 14px;
  }

  .con-slot-tabs {
    margin-top: 1rem;
  }
}
</style>
