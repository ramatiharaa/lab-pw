<template>
  <div class="screen-width">
    <div class="card Flex p-8 w-full md:w-1/2 mx-auto">
      <CustomForm url="/api/auth/login" @success="login" :payload="user">
        <template #content>
          <FormInput
            isRequired
            type="email"
            label="Email"
            v-model="user.email"
            placeholder="Input Email"
          />
          <FormInput
            isRequired
            class="mt-4"
            type="password"
            label="Password"
            v-model="user.password"
            placeholder="Input Password"
          />
        </template>
      </CustomForm>
    </div>
  </div>
</template>

<script>
import CustomForm from "./../../components/CustomForm.vue";
import FormInput from "./../../components/FormInput.vue";

export default {
  components: { CustomForm, FormInput },
  data() {
    return {
      user: {
        email: "",
        password: "",
      },
    };
  },

  methods: {
    login({ data }) {
      this.$store.commit("userData", data);
      this.$router.go(0);
    },
  },
};
</script>