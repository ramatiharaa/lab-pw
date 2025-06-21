<template>
  <div>
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <div class="flex items-center">
            <router-link to="/" class="font-bold italic text-primary">
              Lelang IBIK
            </router-link>
          </div>

          <!-- Navigasi Kanan -->
          <div class="flex items-center space-x-6">
            <router-link
              v-if="!isLoggedIn"
              to="/login"
              class="text-gray-800 hover:underline"
            >
              Login
            </router-link>

            <div v-if="isLoggedIn" class="flex items-center relative" v-click-outside="closeMenu">
              <!-- Lelang Saya -->
              <router-link
                to="/lelang-saya"
                class="mr-4 text-gray-800 hover:underline"
              >
                Lelang Saya
              </router-link>

              <!-- Tombol Profil -->
              <button
                @click.stop="toggleMenu"
                class="flex items-center text-sm rounded-full focus:outline-none"
              >
                <span class="mr-2">{{ user.name || '-' }}</span>
                <img
                  src="/assets/images/profile-icon.png"
                  class="w-8 h-8 rounded-full"
                  alt="Profile"
                />
              </button>

              <!-- Dropdown -->
              <div
                v-if="showProfileMenu"
                class="absolute top-full right-0 mt-2 w-56 bg-white shadow-lg rounded-md ring-1 ring-black ring-opacity-5 z-50"
              >
                <span
                  @click="logout"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
                >
                  Logout
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>
  </div>
</template>

<script>
import { mapState, mapGetters } from "vuex";
import ClickOutside from "vue-click-outside";


export default {
  directives: {
    ClickOutside,
  },
  data() {
    return {
      showProfileMenu: false,
    };
  },
  computed: {
    ...mapState(["user"]),
    ...mapGetters(["isLoggedIn"]),
  },
  methods: {
    logout() {
      this.$store.dispatch("logout");
    },
    toggleMenu() {
      this.showProfileMenu = !this.showProfileMenu;
    },
    closeMenu() {
      this.showProfileMenu = false;
    },
  },
  watch: {
    $route() {
      this.closeMenu();
    },
  },
};
</script>
